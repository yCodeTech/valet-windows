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
}
