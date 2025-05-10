<?php

namespace Valet;

use Exception;

class ValetException extends Exception {
	/**
	 * Construct and return the error message.
	 *
	 * @return string
	 */
	public function getError() {
		$errorMsg = $this->getMessage();
		$errorTypeName = $this->getErrorTypeName($this->getCode());
		$constructTrace = $this->constructTrace();

		return "$errorTypeName: $errorMsg\n\n$constructTrace";
	}

	/**
	 * Get the error type name.
	 * Eg.: Inputs error code `0`, outputs error name `"FATAL"`
	 *
	 * @param mixed $code The numeric error type/code
	 * @return string The error type name
	 */
	private function getErrorTypeName($code) {
		return $code == 0 ? "FATAL" : array_search($code, get_defined_constants(true)['Core']);
	}

	/**
	 * Construct a better-formatted error trace.
	 *
	 * @return string
	 */
	private function constructTrace() {
		$constructTrace = [];
		$count = 0;
		foreach ($this->getTrace() as $key => $value) {
			$count_num = $count++ . ") ";
			$class = $value["class"] ?? "";
			$type = $value["type"] ?? "";
			$func = $value["function"] ?? "";

			$file_n_line = isset($value["file"]) ?
			" ------ " . $value["file"] . ":" . $value["line"] : "";

			$constructTrace[] = $count_num . $class . $type . $func . $file_n_line;
		}
		return implode("\n", $constructTrace);
	}

	/**
	 * If the GitHub API rate limit has exceeded, we need to handle it.
	 *
	 * The API rate limit is 60 requests per hour for unauthenticated requests.
	 *
	 * @param array $headers The headers from the response.
	 * @param string $responseMsg The error message from the response.
	 *
	 * @return array `[$ip, $rateLimit, $timeLeftToReset]` An array containing the IP address, rate limit, and time left to reset.
	 */
	public function githubApiRateLimitExceededError($headers, $responseMsg) {
		// Get the rate limit.
		$rateLimit = $headers["X-RateLimit-Limit"][0];
		// Get the reset time (UTC epoch seconds).
		$resetTime = $headers["X-RateLimit-Reset"][0];

		$timeLeftToReset = $this->calculateTimeToGithubApiLimitReset($resetTime);

		// Get the IP address from the original error response message using regex.
		// The regex pattern matches an IPv4 address.
		// The pattern looks for 1 to 3 digits followed by a dot, repeated 3 times,
		// and then 1 to 3 digits.
		preg_match('/\b(?:\d{1,3}\.){3}\d{1,3}\b/', $responseMsg, $matches);

		// Assign the IP address to a variable if the regex matched, otherwise set it to "unknown".
		$ip = $matches[0] ?: "unknown";

		return [$ip, $rateLimit, $timeLeftToReset];
	}

	/**
	 * Calculate the time left to reset the GitHub API rate limit.
	 *
	 * @param string $resetTime The reset time in UTC epoch seconds.
	 *
	 * @return string The time left to reset the rate limit in a human-readable format.
	 */
	private function calculateTimeToGithubApiLimitReset($resetTime) {
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
