<?php

namespace Valet\Packages;

use function Valet\info;
use function Valet\info_dump;
use function Valet\warning;

class Ansicon extends GithubPackage {
	/**
	 * @var string The name of the package: `ansicon`.
	 */
	protected $packageName = 'ansicon';

	/**
	 * Install Ansicon.
	 */
	public function install() {
		if (!$this->isInstalled()) {
			$ansiconPath = $this->packagePath();
			$zipFilePath = $this->packageZipFilePath();

			$this->files->ensureDirExists($ansiconPath);

			$this->download('https://api.github.com/repos/adoxa/ansicon/releases/latest', 'ansi189-bin.zip', $zipFilePath);

			$this->unzip();

			// Get the contents of the readme.txt file.
			$readmeContents = $this->files->get("$ansiconPath/readme.txt");

			$this->moveFiles("x64");

			// Clean up the package directory.
			$this->cleanUpPackageDirectory("x64");

			// Create a readme.md file with the contents of the readme.txt file.
			// This is just for easier reading.
			$this->files->putAsUser("$ansiconPath/readme.md", $readmeContents);
		}

		// Install Ansicon into CMD's process to automatically add ANSI support.
		$this->cli->runOrExit(
			'"' . $this->packageExe() . '" -i',
			function ($code, $output) {
				warning("Failed to install ansicon.\n$output");
			}
		);
	}

	/**
	 * Uninstall Ansicon.
	 */
	public function uninstall() {
		$this->cli->runOrExit(
			'"' . $this->packageExe() . '" -pu -u',
			function ($code, $output) {
				warning("Failed to uninstall ansicon.\n$output");
			}
		);
	}
}
