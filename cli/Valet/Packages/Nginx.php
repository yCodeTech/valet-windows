<?php

namespace Valet\Packages;

use function Valet\info_dump;
use function Valet\info;

class Nginx extends GithubPackage {
	/**
	 * @var string The name of the package: `nginx`.
	 */
	protected $packageName = 'nginx';

	/**
	 * Download and install the latest version of Nginx.
	 */
	public function install() {
		if (!$this->isInstalled()) {
			$nginxPath = $this->packagePath();
			$zipFilePath = $this->packageZipFilePath();

			$this->files->ensureDirExists($nginxPath);

			$this->download("https://api.github.com/repos/nginx/nginx/releases/latest", "nginx-VERSION.zip", $zipFilePath);

			$this->unzip();

			$this->moveNginxFiles();

			$this->cleanUpPackageDirectory();
		}
	}

	/**
	 * Move the required Nginx files into the package directory.
	 */
	private function moveNginxFiles() {
		// Loop through a list of directory names from the zip file path.
		foreach ($this->files->listTopLevelZipDirs($this->packageZipFilePath()) as $dir) {
			// If the directory name contains 'nginx-', set it as the required directory.
			if (str_contains($dir, 'nginx-')) {
				$requiredDir = $dir;
				break;
			}
		}

		// Ensure $requiredDir is defined before moving files.
		if (isset($requiredDir)) {
			// Move the required files from the subdirectory to the package directory.
			$this->moveFiles($requiredDir);
		}
		else {
			info("Nginx required directory not found.");
		}
	}
}
