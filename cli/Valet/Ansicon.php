<?php

namespace Valet;

class Ansicon {
	/**
	 * @var CommandLine
	 */
	protected $cli;

	/**
	 * Create a new Ansicon instance.
	 *
	 * @param  CommandLine  $cli
	 * @return void
	 */
	public function __construct(CommandLine $cli) {
		$this->cli = $cli;
	}

	/**
	 * Install Ansicon.
	 *
	 * @return void
	 */
	public function install() {
		$this->cli->runOrExit(
			'"' . valetBinPath() . 'ansicon/ansicon.exe" -i',
			function ($code, $output) {
				warning("Failed to install ansicon.\n$output");
			}
		);
	}

	/**
	 * Uninstall Ansicon.
	 *
	 * @return void
	 */
	public function uninstall() {
		$this->cli->runOrExit(
			'"' . valetBinPath() . 'ansicon/ansicon.exe" -pu -u',
			function ($code, $output) {
				warning("Failed to uninstall ansicon.\n$output");
			}
		);
	}
}
