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

abstract class Package {
	/**
	 * @var CommandLine
	 */
	protected $cli;

	/**
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * @var Client Guzzle client for making HTTP requests.
	 */
	protected $client;

	/**
	 * @var string The name of the package.
	 */
	protected $packageName;

	/**
	 * @var string The name of the package executable.
	 *
	 * Optionally used to specify the name of the executable file
	 * if it differs from the package name.
	 */
	protected $packageExeName;

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

	/**
	 * Download the package.
	 *
	 * @param string $url The URL for the download.
	 * @param string $filename The name of the file to download.
	 * @param string $filePath The path where the file will be saved and as what name.
	 *
	 * @return void
	 */
	abstract protected function download(string $url, string $filename, string $filePath);


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
	 * Get the response of the API.
	 *
	 * @param string $apiUrl The API URL to query.
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
	 * Download a file from the specified URL and save it to the specified path.
	 *
	 * @param string $downloadUrl The URL to download the file from.
	 * @param string $filePath The path where the file will be saved.
	 *
	 * @uses GuzzleHttp\Client::get
	 */
	public function downloadFile(string $downloadUrl, string $filePath) {
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
	public function packagePath(): string {
		return valetBinPath() . $this->packageName;
	}

	/**
	 * Get the path to the package executable.
	 *
	 * @return string
	 */
	public function packageExe(): string {
		$name = $this->packageExeName ?: $this->packageName;
		return $this->packagePath() . "/$name.exe";
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
	 * Clean up the package directory that was downloaded and unzipped.
	 * Remove all unnecessary directories and files.
	 *
	 * @param string $requiredDir The name of the required directory to keep.
	 * If empty, no directories will be removed.
	 * @param array $searchStrings An array of strings to search for in the filenames.
	 * If empty, no files will be removed.
	 */
	protected function cleanUpPackageDirectory($requiredDir = "", $searchStrings = []) {

		/** Remove unnecessary directories **/

		// If the required directory is not empty...
		if (!empty($requiredDir)) {
			// For each unnecessary directory in the package directory...
			foreach ($this->getUnnecessaryDirs($requiredDir) as $dir) {
			// Remove all unnecessary directories and their files.
				$this->files->unlink($this->packagePath() . "/$dir");
			}
		}

		/** Remove unnecessary files **/

		// If the search strings array is not empty...
		if (!count($searchStrings) != 0) {
			// For each unnecessary file in the package directory...
			foreach ($this->getUnnecessaryFiles($searchStrings) as $file) {
				// Remove the file.
				$this->files->unlink($this->packagePath() . "/$file");
			}
		}

		/** Remove the zip file **/

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
	 * Get the unnecessary files by a search string to remove in the package directory.
	 *
	 * @param array $searchStrings Strings to search for in the filenames.
	 *
	 * @return array
	 */
	protected function getUnnecessaryFiles(array $searchStrings): array {
		return collect($this->files->scandir($this->packagePath()))->filter(function ($file) use ($searchStrings) {
			foreach ($searchStrings as $string) {
				// If the filename contains the string, then we add it to the
				// collection of unnecessary files that we want to delete.
				return str_contains($file, $string);
			}
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
}