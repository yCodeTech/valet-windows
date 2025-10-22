<?php

namespace Valet\Packages;

use function Valet\info;
use function Valet\info_dump;
use function Valet\error;

abstract class GithubPackage extends Package {
	/*--------------------------------------------------------------*
	 * Abstract methods that must be implemented by the subclasses. *
	 *--------------------------------------------------------------*/

	// Abstract methods are defined in the parent class `Package`,
	// so we don't need to define them here.

	/*------------------------------------------------*
	 * Common methods that don't need to be abstracts *
	 *------------------------------------------------*/

	/**
	 * Download the package from GitHub.
	 *
	 * @param string $githubApiUrl The GitHub API URL for the release.
	 * @param string $filename The name of the file to download.
	 * @param string $filePath The path where the file will be saved and as what name.
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

		$this->downloadFile($downloadUrl, $filePath);
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
