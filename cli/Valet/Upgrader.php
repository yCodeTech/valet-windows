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
	public function onEveryRun(): void {
		$this->prunePathsFromConfig();
		$this->pruneSymbolicLinks();
		$this->upgradeSymbolicLinks();
	}

	/**
	 * Prune all non-existent paths from the configuration.
	 */
	public function prunePathsFromConfig() {
		try {
			$this->config->prune();
		}
		catch (\JsonException $e) {
			warning('Invalid configuration file at ' . $this->config->path() . '.');
			exit;
		}
	}

	public function pruneSymbolicLinks() {
		$this->site->pruneLinks();
	}

	/**
	 * Upgrade and convert all junction links to real symbolic links.
	 *
	 * This is a one-time upgrade that will be run when Valet is first installed.
	 *
	 * @return void
	 */
	public function upgradeSymbolicLinks() {
		// Check if the symlinks have already been upgraded, by checking if a key exists in
		// the config. If not, then upgrade them.
		if ($this->config->get("symlinks_upgraded", false) === false) {
			info("Upgrading your linked sites from the old junction links to symbolic links...");

			$this->files->convertJunctionsToSymlinks($this->site->sitesPath());
			// Add a new key to the config file to indicate that the symlinks have been upgraded.
			// This will prevent the upgrade from running again, since it is a one-time upgrade.
			$this->config->updateKey("symlinks_upgraded", true);

			info("Successfully upgraded junction links to symbolic links.");
		}
	}
}
