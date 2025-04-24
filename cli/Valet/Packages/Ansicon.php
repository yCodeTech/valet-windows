<?php

namespace Valet\Packages;

use function Valet\info;
use function Valet\info_dump;
use function Valet\warning;
use function Valet\valetBinPath;

class Ansicon extends GithubPackage {
	/**
	 * Install Ansicon.
	 *
	 * @return void
	 */
	public function install() {
		if (!$this->isInstalled("ansicon")) {
			$zipFilePath = valetBinPath() . 'ansicon/ansi189-bin.zip';
			$ansiconPath = valetBinPath() . 'ansicon';

			$this->files->ensureDirExists($ansiconPath);

			$this->download('https://api.github.com/repos/adoxa/ansicon/releases/latest', 'ansi189-bin.zip', $zipFilePath);

			$this->files->unzip($zipFilePath, $ansiconPath);

			// Get the contents of the readme.txt file.
			$readmeContents = $this->files->get("$ansiconPath/readme.txt");

			// Clean up the package directory.
			$this->cleanUpPackageDirectory($ansiconPath);

			// Create a readme.md file with the contents of the readme.txt file.
			// This is just for easier reading.
			$this->files->putAsUser("$ansiconPath/readme.md", $readmeContents);
		}

		// Install Ansicon into CMD's process to automatically add ANSI support.
		$this->cli->runOrExit(
			'"' . $this->packagePath("ansicon") . '" -i',
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
			'"' . $this->packagePath("ansicon") . '" -pu -u',
			function ($code, $output) {
				warning("Failed to uninstall ansicon.\n$output");
			}
		);
	}
}
