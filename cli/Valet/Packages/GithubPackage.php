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
		// Get the response from the GitHub API OR handle errors.
		$response = $this->getApiResponse($githubApiUrl,
			function ($errorCode, $responseHeaders, $responseMsg) use ($githubApiUrl) {

				if (str_contains($responseMsg, 'API rate limit exceeded')) {
					$rateLimit = $responseHeaders["X-RateLimit-Limit"][0];

					$timeLeftToReset = $this->calculateTimeToApiRateLimitReset($responseHeaders["X-RateLimit-Reset"][0]);

					// Print the error messages.
					error("\n\nThe GitHub API rate limit has been exceeded for your IP address. The rate limit is $rateLimit requests per hour.\n\n");

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
		);

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
	 * Get the response of the API.
	 *
	 * @param string $apiUrl The GitHub API URL to query.
	 * @param callable|null $onError Optional callback to handle errors.
	 *
	 * @return mixed The response from the API, if successful.
	 * @throws \ValetException
	 */
	protected function getApiResponse(string $apiUrl, ?callable $onError = null) {

		// If no error callback is provided, set it to a default function that does nothing.
		$onError = $onError ?: function () {
		};

		// Try to get the information from the API, otherwise
		// catch the exception and handle it.
		try {
			// Process response normally...
			$response = json_decode($this->client->get($apiUrl)->getBody());
		}
		catch (ClientException $e) {
			// An exception was raised but there is an HTTP response body
			// with the exception (in case of 404 and similar errors)
			$response = $e->getResponse();

			$errorCode = $response->getStatusCode();

			$responseHeaders = $response->getHeaders();
			$responseBody = json_decode($response->getBody());
			$responseMsg = $responseBody->message;

			// If an error callback is provided...
			if ($onError) {
				// Call the error callback with the error code and message.
				$onError($errorCode, $responseHeaders, $responseMsg);
			}
			// Otherwise if no error callback is provided...
			else {
				// Throw the default error with the details.
				$error = "Error Code: $errorCode\n";
				$error .= "Error Message: $responseMsg\n";
				$error .= "The API URL queried is: $apiUrl\n";

				error($error, true);
			}
		}

		return $response;
	}

	/**
	 * Get the path to the package directory.
	 *
	 * @param string $packageName
	 *
	 * @return string
	 */
	public function packagePath(): string {
		return valetBinPath() . $this->packageName;
	}

	/**
	 * Get the path to the package executable.
	 *
	 * @return string
	 */
	public function packageExe(): string {
		return $this->packagePath() . "/$this->packageName.exe";
	}

	/**
	 * Get the path to the package zip file.
	 *
	 * @return string
	 */
	protected function packageZipFilePath(): string {
		return $this->packagePath() . "/$this->packageName.zip";
	}

	/**
	 * Clean up the package directory that was downloaded and unzipped from GitHub.
	 * Remove all unnecessary directories and files.
	 *
	 * @param array $unnecessaryDirsToRemove
	 *
	 * @return void
	 */
	protected function cleanUpPackageDirectory($requiredDir = "") {
		// For each unnecessary directory in the package directory...
		foreach ($this->getUnnecessaryDirs($requiredDir) as $dir) {
			// Remove all unnecessary directories and their files.
			$this->files->unlink($this->packagePath() . "/$dir");
		}

		$this->removeZip();
	}

	/**
	 * Get the unnecessary directories to remove in the package directory.
	 *
	 * @param string $requiredDir
	 *
	 * @return array
	 */
	protected function getUnnecessaryDirs($requiredDir) {
		return collect($this->files->listTopLevelZipDirs($this->packageZipFilePath()))->reject(function ($dir) use ($requiredDir) {
			// If the directory name contains the required directory name, then we remove
			// it from the collection of unnecessary directories that we want to delete.
			return str_contains($dir, $requiredDir);
		})->all();
	}

	/**
	 * Unzip the package zip file into the package directory.
	 */
	protected function unzip() {
		$this->files->unzip($this->packageZipFilePath(), $this->packagePath());
	}

	/**
	 * Remove the zip file after extracting its contents.
	 *
	 */
	protected function removeZip() {
		$this->files->unlink($this->packageZipFilePath());
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

	/**
	 * Calculate the time left to reset the GitHub API rate limit.
	 *
	 * @param string $resetTime The reset time in UTC epoch seconds.
	 *
	 * @return string The time left to reset the rate limit in a human-readable format.
	 */
	private function calculateTimeToApiRateLimitReset($resetTime) {
		// Create new DateTime objects for the reset time and the current time.
		$reset_time = new \DateTime("@$resetTime");
		$current_time = new \DateTime("now");

		// Get the difference between the 2 times.
		$timeDifference = $reset_time->diff($current_time);

		// Get the difference in minutes and seconds.
		// The DateInterval object has many properties, including minutes and seconds,
		// which we can directly access.
		$mins = $timeDifference->i;
		$secs = $timeDifference->s;

		// Format the minutes and seconds into a human-readable string.
		// If the minutes or seconds equals 1, we need to use the singular form
		// of "minute" or "second".
		$minsTxt = $mins === 1 ? "$mins minute" : "$mins minutes";
		$secsTxt = $secs === 1 ? "$secs second" : "$secs seconds";

		return "$minsTxt and $secsTxt";
	}
}
