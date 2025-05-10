<?php

namespace Valet\Packages;

use Valet\CommandLine;
use Valet\Filesystem;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Composer\CaBundle\CaBundle;

use function Valet\info;
use function Valet\info_dump;
use function Valet\error;
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

	/**
	 * @var string The name of the package.
	 */
	protected $packageName;

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

	 * @return bool
	 */
	public function isInstalled() {
		return $this->files->exists($this->packageExe());
	}


	/**
	 * Download the package from GitHub.
	 *
	 * @param string $githubApiUrl The GitHub API URL for the release.
	 * @param string $filename The name of the file to download.
	 * @param string $filePath The path where the file will be saved and as what name.
	 *
	 * @return void
	 */
	protected function download(string $githubApiUrl, string $filename, string $filePath) {
		// Try to get the information from the GitHub API, otherwise
		// catch the exception and handle it.
		try {
			// Process response normally...
			$response = json_decode($this->client->get($githubApiUrl)->getBody());
		}
		catch (ClientException $e) {
			// An exception was raised but there is an HTTP response body
			// with the exception (in case of 404 and similar errors)
			$response = $e->getResponse();

			$errorCode = $response->getStatusCode();

			$responseHeaders = $response->getHeaders();
			$responseBody = json_decode($response->getBody());
			$responseMsg = $responseBody->message;

			if (str_contains($responseMsg, 'API rate limit exceeded')) {
				[$ip, $rateLimit, $timeLeftToReset] = \ValetException::githubApiRateLimitExceededError($responseHeaders, $responseMsg);

				// Print the error messages.
				error("\n\nThe GitHub API rate limit has been exceeded for your IP address ($ip). The rate limit is $rateLimit requests per hour.\n\n");

				info("\nThe rate limit will reset in $timeLeftToReset.");

				error("API rate limit exceeded", true);
			}
			else {
				$error = "Error Code: $errorCode\n";
				$error .= "Error Message: $responseMsg\n";
				$error .= "The GitHub API URL queried is: $githubApiUrl\n";

				error($error, true);
			}
		}

		// If the 'assets' property exists in the response, it means we are downloading
		// a release asset.
		if (property_exists($response, 'assets')) {
			// Find the asset with the specified filename and get the download URL.
			foreach ($response->assets as $asset) {
				// If the filename contains "VERSION", then we need to use regex
				// to match the version number in the filename.
				if (str_contains($filename, "VERSION")) {
					$filename = $this->getVersionedFilename($filename, $asset->name)?? $filename;
				}

				// If the asset name equals the filename, then get the download URL.
				if ($asset->name === $filename) {
					$downloadUrl = $asset->browser_download_url;
					break;
				}
			}
		}
		// If the response's 'name' property equals "README.md", then we are downloading
		// the readme file directly from the repo.
		elseif ($response->name === "README.md") {
			// Get the raw readme URL.
			$downloadUrl = $response->download_url;
		}
		else {
			error("The GitHub API response doesn't have the expected properties. The API URL queried is: $githubApiUrl\n", true);
		}

		if (!isset($downloadUrl)) {
			error("The download URL was not found in the response. The API URL queried is: $githubApiUrl\n", true);
		}

		// Download the file via Guzzle.
		$this->client->get($downloadUrl, [
			\GuzzleHttp\RequestOptions::SINK => $filePath
		]);
	}

	/**
	 * Get the path to the package directory.
	 *
	 * @param string $packageName
	 *
	 * @return string
	 */
	protected function packagePath(): string {
		return valetBinPath() . $this->packageName;
	}

	/**
	 * Get the path to the package executable.
	 *
	 * @return string
	 */
	protected function packageExe(): string {
		return $this->packagePath() . "/$this->packageName.exe";
	}

	/**
	 * Clean up the package directory that was downloaded and unzipped from GitHub.
	 * Remove all unnecessary directories and files.
	 *
	 * @param mixed $zipFilePath
	 *
	 * @param array $unnecessaryDirsToRemove
	 *
	 * @return void
	 */
	protected function cleanUpPackageDirectory($zipFilePath, $requiredDir = "") {
		// For each unnecessary directory in the package directory...
		foreach ($this->getUnnecessaryDirs($zipFilePath, $requiredDir) as $dir) {
			// Remove all unnecessary directories and their files.
			$this->files->unlink($this->packagePath() . "/$dir");
		}

		$this->removeZip($zipFilePath);
	}

	/**
	 * Get the unnecessary directories to remove in the package directory.
	 *
	 * @param string $zipFilePath
	 * @param string $requiredDir
	 *
	 * @return array
	 */
	protected function getUnnecessaryDirs($zipFilePath, $requiredDir) {
		return collect($this->files->listTopLevelZipDirs($zipFilePath))->reject(function ($dir) use ($requiredDir) {
			// If the directory name contains the required directory name, then we remove
			// it from the collection of unnecessary directories that we want to delete.
			return str_contains($dir, $requiredDir);
		})->all();
	}

	/**
	 * Remove the zip file after extracting its contents.
	 *
	 * @param string $zipFilePath
	 */
	protected function removeZip($zipFilePath) {
		$this->files->unlink($zipFilePath);
	}

	/**
	 * Move files to the main package directory.
	 *
	 * @param string $dirName
	 */
	protected function moveFiles($dirName) {
		$packagePath = $this->packagePath();

		// For each item in the specified directory...
		foreach ($this->files->scandir("$packagePath/$dirName") as $item) {
			// Move the item to the main package directory.
			// The item could be a file or a directory.
			$this->files->move("$packagePath/$dirName/$item", "$packagePath/$item");
		}

		// Remove the original directory after moving its contents.
		$this->files->unlink("$packagePath/$dirName");
	}

	/**
	 * Get the versioned filename from the asset name, replacing the "VERSION"
	 * placeholder with the version number obtained via regex.
	 *
	 * @param string $filename The filename with "VERSION" placeholder.
	 * @param string $assetName The asset name from the GitHub API response.
	 *
	 * @return string|null The versioned filename or null if not found.
	 */
	protected function getVersionedFilename($filename, $assetName) {

		$filenameArray = explode('VERSION', $filename);
		// Escape special regex characters in the filename parts.
		$filenamePart1 = preg_quote($filenameArray[0], '/');
		$filenamePart2 = preg_quote($filenameArray[1], '/');

		// Regex to the filename parts 1 and 2 and a version number in between.
		// Part 1 could be "nginx\-" and part 2 could be "\.zip"
		// (with escaped special characters).
		// So the regex would look like this:
		// nginx\-(\d+\.\d+\.\d+)\.zip"
		// Example match: "nginx-1.28.0.zip"
		$regex = "/$filenamePart1(\d+\.\d+\.\d+)$filenamePart2/";
		preg_match($regex, $assetName, $matches);

		if (!empty($matches)) {
			return $matches[0];
		}
	}
}
