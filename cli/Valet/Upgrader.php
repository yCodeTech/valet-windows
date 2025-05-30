<?php

namespace Valet;

class Upgrader {
	/**
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * @var Configuration
	 */
	protected $config;

	/**
	 * @var Site
	 */
	protected $site;

	public function __construct(Filesystem $files, Configuration $config, Site $site) {
		$this->files = $files;
		$this->config = $config;
		$this->site = $site;
	}

	/**
	 * Run all the upgrades that should be run every time Valet commands are run.
	 */
	public function onEveryRun() {
		// Only run if the Valet home path exists.
		if ($this->files->isDir(Valet::homePath())) {
			$this->prunePathsFromConfig();
			$this->pruneSymbolicLinks();
			$this->upgradeSymbolicLinks();
			$this->lintNginxConfigs();
			$this->upgradeNginxSiteConfigs();
			$this->fixOldSampleValetDriver();
		}
	}

	/**
	 * Prune all non-existent paths from the configuration.
	 */
	private function prunePathsFromConfig() {
		try {
			$this->config->prune();
		}
		catch (\JsonException $e) {
			warning('Invalid configuration file at ' . $this->config->path() . '.');
			exit;
		}
	}

	/**
	 * Prune all symbolic links that no longer point to a valid site.
	 */
	private function pruneSymbolicLinks() {
		$this->site->pruneLinks();
	}

	/**
	 * Upgrade and convert all Windows junction links to real symbolic links.
	 *
	 * This is a one-time upgrade that will be run when Valet is first installed.
	 */
	private function upgradeSymbolicLinks() {
		if ($this->shouldUpgradeSymbolicLinks()) {
			info("Upgrading your linked sites from the old junction links to symbolic links...");
			// Convert all junction links to symbolic links.
			$this->files->convertJunctionsToSymlinks($this->site->sitesPath());

			// Add a new key to the config file to indicate that the symlinks have been upgraded.
			// This will prevent the upgrade from running again, since it is a one-time upgrade.
			$this->config->updateKey("symlinks_upgraded", true);

			info("Successfully upgraded junction links to symbolic links.");
		}
	}

	/**
	 * Check if the symbolic links should be upgraded.
	 *
	 * @return bool Returns a boolean indicating whether the symlinks should be upgraded.
	 *
	 * The symlinks should be upgraded if:
	 *
	 * 1. The symlinks have not been upgraded yet (`$symlinksUpgraded` is `false`).
	 * 2. The sites directory is not empty (`$isDirEmpty` is `false`).
	 */
	private function shouldUpgradeSymbolicLinks() {
		// Get the value of the "symlinks_upgraded" key from the config.
		// If the key doesn't exist, it will return false.
		$symlinksUpgraded = $this->config->get("symlinks_upgraded", false);

		// Check if the sites directory is empty.
		$isDirEmpty = $this->files->isDirEmpty($this->site->sitesPath());

		// Check if the symlinks have not been upgraded yet AND the sites directory is not empty.
		return !$symlinksUpgraded && !$isDirEmpty;
	}

	/**
	 * Lint the Nginx configuration files.
	 *
	 * @param boolean $returnOutput
	 *
	 * @return string|null
	 */
	private function lintNginxConfigs($returnOutput = false) {
		if (resolve(Packages\Nginx::class)->isInstalled()) {
			return \Nginx::lint($returnOutput);
		}
	}

	/**
	 * Upgrade Nginx site configurations.
	 *
	 * This method checks the Nginx configuration files for deprecated `http2` param
	 * and `http2_push_preload` directive, then upgrades the site configurations accordingly.
	 */
	private function upgradeNginxSiteConfigs() {
		$output = $this->lintNginxConfigs(true);

		// If output is not empty...
		if (!empty($output)) {
			$stringsToCheck = ['the "listen ... http2" directive is deprecated', '"http2_push_preload" directive is obsolete'];

			$outputArray = explode("\r\n", $output);

			// For each line in the output...
			foreach ($outputArray as $line) {
				// If the line contains any of the strings in the array...
				if (str_contains_any($line, $stringsToCheck)) {
					// Check if the line contains "in [path]:[line number]"
					// eg. "in C:/path/to/file.conf:123"; and if it does add the
					// matched string to a variable.
					if (preg_match('/in (.*):\d+$/', $line, $matches)) {
						// Get the site name from file path in the matched string,
						// ie. gets the filename (site.conf) and removes the extension.
						$site = basename($matches[1], ".conf");

						// If the site is isolated...
						if ($this->site->isIsolated($site)) {
							// Get the PHP version for the site.
							$phpVersion = $this->site->customPhpVersion($site);

							// Unisolate the site and re-isolate it using the phpVersion to
							// upgrade the Nginx config file.
							$this->site->unisolate($site);
							$this->site->isolate($phpVersion, $site);
						}
						// If the site is secured...
						elseif ($this->site->isSecured($site)) {
							// Unsecure the site and re-secure it to upgrade
							// the Nginx config file.
							$this->site->unsecure($site);
							$this->site->secure($site);
						}
					}
				}
			}
		}
	}

	/**
	 * If the user has the old `SampleValetDriver` without the Valet namespace,
	 * replace it with the new `SampleValetDriver` that uses the namespace.
	 */
	public function fixOldSampleValetDriver(): void {
		$samplePath = Valet::homePath() . '/Drivers/SampleValetDriver.php';

		if ($this->files->exists($samplePath)) {
			$contents = $this->files->get($samplePath);

			if (! str_contains($contents, 'namespace')) {
				if ($contents !== $this->files->getStub('LegacySampleValetDriver.php')) {
					warning('Existing SampleValetDriver.php has been customized.');
					warning("Backing up at '$samplePath.bak'");

					$this->files->putAsUser(
						"$samplePath.bak",
						$contents
					);
				}

				$this->files->putAsUser(
					$samplePath,
					$this->files->getStub('SampleValetDriver.php')
				);
			}
		}
	}
}
