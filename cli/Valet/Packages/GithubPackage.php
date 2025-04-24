<?php

namespace Valet\Packages;

use Valet\CommandLine;
use Valet\Filesystem;

use GuzzleHttp\Client;
use Composer\CaBundle\CaBundle;

use function Valet\info;
use function Valet\info_dump;
use function Valet\valetBinPath;

abstract class GithubPackage {
	/**
	 * @var CommandLine
	 */
	protected $cli;

	/**
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * @var Client
	 */
	protected $client;

	public function __construct(CommandLine $cli, Filesystem $files) {
		$this->cli = $cli;
		$this->files = $files;
		$this->client = new Client([
			\GuzzleHttp\RequestOptions::VERIFY => CaBundle::getSystemCaRootBundlePath()
		]);
	}

	/*--------------------------------------------------------------*
	 * Abstract methods that must be implemented by the subclasses. *
	 *--------------------------------------------------------------*/

	/**
	 * Install the package.
	 *
	 * @return void
	 */
	abstract public function install();


	/*------------------------------------------------*
	 * Common methods that don't need to be abstracts *
	 *------------------------------------------------*/


	/**
	 * Check if the package is installed.
	 *
	 * @param string $packageName
	 * @return bool
	 */
	protected function isInstalled(string $packageName) {
		return $this->cli->run($this->packagePath($packageName) . " /?")->isSuccessful();
	}


	/**
	 * Download the package from GitHub.
	 *
	 * @param string $githubApiUrl The GitHub API URL for the release.
	 * @param string $fileName The name of the file to download.
	 * @param string $zipFilePath The path where the zip file will be saved.
	 *
	 * @return void
	 */
	protected function download(string $githubApiUrl, string $fileName, string $zipFilePath) {
		$release = json_decode($this->client->get($githubApiUrl)->getBody());

		foreach ($release->assets as $asset) {
			if ($asset->name === $fileName) {
				$downloadUrl = $asset->browser_download_url;
				break;
			}
		}

		// Download the zip file via Guzzle.
		$this->client->get($downloadUrl, [
			\GuzzleHttp\RequestOptions::SINK => $zipFilePath
		]);
	}

	/**
	 * Get the path to the package executable.
	 *
	 * @param string $packageName
	 *
	 * @return string
	 */
	protected function packagePath(string $packageName): string {
		return valetBinPath() . "$packageName/$packageName.exe";
	}

	/**
	 * Clean up the package directory that was downloaded and unzipped from GitHub.
	 * Move the x64 files to the main package directory and remove all other
	 * irrelevant directories and files.
	 *
	 * @param mixed $packagePath
	 *
	 * @return void
	 */
	protected function cleanUpPackageDirectory($packagePath) {
		// For each directory in the package directory...
		foreach ($this->files->scandir($packagePath) as $dir) {
			// Move the necessary files to the package directory.
			$this->moveX64Files($dir, $packagePath);

			// Remove all unnecessary directories and files.
			$this->files->unlink("$packagePath/$dir");
		}
	}

	/**
	 * Move x64 files to the main gsudo directory.
	 *
	 * @param string $dir
	 * @param string $packagePath
	 */
	protected function moveX64Files($dir, $packagePath) {
		// If the directory is x64...
		if ($dir === 'x64') {
			// For each file...
			foreach ($this->files->scandir("$packagePath/$dir") as $file) {
				// Move the file to the package's main directory.
				$this->files->move("$packagePath/$dir/$file", "$packagePath/$file");
			}
		}
	}
}