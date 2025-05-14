<?php

namespace Valet;

use DomainException;
use GuzzleHttp\Client;

class Share {
	/**
	 * @var CommandLine
	 */
	protected $cli;

	/**
	 * @var Configuration
	 */
	protected $config;

	/**
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * The supported share tools.
	 * @var string[]
	 */
	protected $share_tools;

	/**
	 * @var object
	 */
	protected $current_tool_instance;


	/**
	 * Create a new Nginx instance.
	 *
	 * @param CommandLine $cli
	 * @param Configuration $config
	 * @return void
	 */
	public function __construct(CommandLine $cli, Configuration $config, Filesystem $files) {
		$this->cli = $cli;
		$this->config = $config;
		$this->files = $files;

		/**
		 * Define the supported share tools.
		 */
		$this->share_tools = [
			"ngrok"
		];

		// Create the share tool instance.
		$this->createShareToolInstance();
	}

	/**
	 * Main method to use which returns the share tool's class instance
	 * to be able to access the methods in a chainable way.
	 *
	 * @return object
	 */
	public function shareTool() {
		return $this->getShareToolInstance();
	}

	/**
	 * Create the share tool class namespace using the current tool
	 * and create the child class instance.
	 *
	 * @return void
	 */
	private function createShareToolInstance() {
		$tool = $this->getCurrentShareTool();
		if ($tool && !isset($this->current_tool_instance)) {
			// Construct the share tool's class namespace.
			$tool_class = "Valet\\ShareTools\\$tool";

			// Create the share tool's child class instance via a dynamic class namespace.
			$this->current_tool_instance = new $tool_class($this->cli, $this->files);
		}
	}

	/**
	 * Get the share tool child class instance.
	 * @return object
	 */
	private function getShareToolInstance() {
		return $this->current_tool_instance;
	}

	/**
	 * Get the share tools as a string.
	 *
	 * @return string
	 */
	public function getShareTools() {
		return preg_replace('/,\s([^,]+)$/',
			' or $1',
			implode(', ', array_map(fn ($t) => "`$t`", $this->share_tools))
		);
	}

	/**
	 * Check if the specified tool is valid.
	 *
	 * @param string $tool
	 * @return bool
	 */
	public function isToolValid($tool) {
		return (in_array($tool, $this->share_tools) && class_exists($tool));
	}

	/**
	 * Get the current share tool from the config.
	 *
	 * @return string|null
	 */
	public function getCurrentShareTool() {
		return $this->config->get("share-tool") ?? null;
	}
}