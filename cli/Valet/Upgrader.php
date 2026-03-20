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

	/**
	 * Sites that have had their Nginx configs upgraded during this run of Valet.
	 *
	 * @var array<string, bool>
	 */
	protected $upgradedNginxSites = [];

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
			$this->upgradeDeprecatedNginxConfigDirectives();
			$this->fixOldSampleValetDriver();
			$this->upgradeNginxSitePhpPortOverrides();
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
	 * @param bool $returnOutput
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
	 * To upgrade the Nginx config files for each site, this method will:
	 * - Unisolate and reisolate isolated sites
	 * - Delete and recreate proxy sites
	 * - Unsecure and resecure secured sites
	 *
	 * @param string $site The site to upgrade.
	 */
	private function upgradeNginxSiteConfigs($site) {
		info("Upgrading Nginx config for site '{$site}'...");

		$didUpgrade = false;

		// If the site is isolated...
		if ($this->site->isIsolated($site)) {
			// Get the PHP version for the site.
			$phpVersion = $this->site->customPhpVersion($site);

			// Unisolate the site and re-isolate it using the phpVersion to
			// upgrade the Nginx config file.
			$this->site->unisolate($site);
			$this->site->isolate($phpVersion, $site);
			$didUpgrade = true;
		}
		// If the site is a proxy...
		elseif ($this->site->isProxy($site)) {
			// Get the proxy host and whether the site is secured.
			$host = $this->site->getProxyHostForSite($site);
			$secured = $this->site->isSecured($site);

			// Delete the proxy and re-create it using the host and
			// secured values to upgrade the Nginx config file.
			$this->site->proxyDelete($site);
			$this->site->proxyCreate($site, $host, $secured);
			$didUpgrade = true;
		}
		// If the site is secured...
		elseif ($this->site->isSecured($site)) {
			// Unsecure the site and re-secure it to upgrade
			// the Nginx config file.
			$this->site->unsecure($site);
			$this->site->secure($site);
			$didUpgrade = true;
		}

		if ($didUpgrade) {
			$this->upgradedNginxSites[$site] = true;
		}
	}

	/**
	 * Upgrade deprecated Nginx configuration directives.
	 *
	 * This method checks the Nginx configuration files for deprecated `http2` param and `http2_push_preload` directive, then prompts the user to upgrade their Nginx site configurations accordingly.
	 */
	private function upgradeDeprecatedNginxConfigDirectives() {
		$output = $this->lintNginxConfigs(true);

		// If output is not empty...
		if (!empty($output)) {
			$stringsToCheck = [
				'the "listen ... http2" directive is deprecated',
				'"http2_push_preload" directive is obsolete'
			];

			$outputArray = explode("\r\n", $output);

			$sitesToUpgrade = [];

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
						// Add the site to the sitesToUpgrade array.
						// This ensures that if a site has multiple deprecated directives,
						// it will only be added to the array once.
						$sitesToUpgrade[$site] = true;
					}
				}
			}

			// If there are any sites to upgrade...
			if (!empty($sitesToUpgrade)) {
				warning('Your Nginx configuration files contain some deprecated directives that need to be updated.');

				// Upgrade the Nginx config files for each site that needs to be upgraded.
				foreach (array_keys($sitesToUpgrade) as $site) {
					$this->upgradeNginxSiteConfigs($site);
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

	/**
	 * Upgrade all Nginx site configs to use per-site PHP port overrides.
	 */
	private function upgradeNginxSitePhpPortOverrides() {
		// If the PHP port definitions have already been upgraded, skip.
		if (!$this->shouldUpgradeNginxSitePhpPortOverrides()) {
			return;
		}

		// If the Nginx config directory doesn't exist, skip and mark it as upgraded to prevent
		// this from running again.
		if (!$this->files->exists($this->site->nginxPath())) {
			$this->config->updateKey('php_port_overrides_upgraded', true);
			return;
		}

		$upgraded = 0;
		$tld = $this->config->read()['tld'];

		// Get all the Nginx config files for the sites.
		foreach ($this->files->scandir($this->site->nginxPath()) as $file) {
			// If the file doesn't end with ".{tld}.conf", skip it.
			if (!str_ends_with($file, ".{$tld}.conf")) {
				continue;
			}

			// Remove the ".conf" extension from the filename to get the site name.
			$site = basename($file, '.conf');

			// If the site has already been upgraded during this run, skip it.
			if (isset($this->upgradedNginxSites[$site])) {
				continue;
			}

			$this->upgradeNginxSiteConfigs($site);
			$upgraded++;
		}

		if ($upgraded > 0) {
			info("Upgraded {$upgraded} Nginx site config(s) to the new PHP port override format.");
		}

		$this->config->updateKey('php_port_overrides_upgraded', true);
	}

	/**
	 * Determine whether Nginx site PHP port overrides should be upgraded.
	 *
	 * @return bool
	 */
	private function shouldUpgradeNginxSitePhpPortOverrides() {
		return !$this->config->get('php_port_overrides_upgraded', false);
	}
}
