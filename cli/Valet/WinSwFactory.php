<?php

namespace Valet;

class WinSwFactory {
	/**
	 * @var CommandLine
	 */
	protected $cli;

	/**
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * Create a new factory instance.
	 *
	 * @param CommandLine $cli
	 * @param Filesystem $files
	 * @return void
	 */
	public function __construct(CommandLine $cli, Filesystem $files) {
		$this->cli = $cli;
		$this->files = $files;

		// Download and install the WinSW package if it's not already installed.
		(new \Valet\Packages\WinSW($cli, $files))->install();
	}

	/**
	 * Make a new WinSW instance.
	 *
	 * @param string $service
	 * @param string $serviceId
	 * @return WinSW
	 */
	public function make(string $service, string $serviceId) {
		return new WinSW(
			$service,
			$serviceId,
			$this->cli,
			$this->files
		);
	}
}