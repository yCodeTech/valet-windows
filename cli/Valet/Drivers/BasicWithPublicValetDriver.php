<?php

namespace Valet\Drivers;

class BasicWithPublicValetDriver extends ValetDriver {
	/**
	 * Determine if the driver serves the request.
	 *
	 * @param string $sitePath
	 * @param string $siteName
	 * @param string $uri
	 *
	 * @return bool
	 */
	public function serves($sitePath, $siteName, $uri) {
		return is_dir("$sitePath/public/");
	}

	/**
	 * Take any steps necessary before loading the front controller for this driver.
	 *
	 * @param string $sitePath
	 * @param string $siteName
	 * @param string $uri
	 */
	public function beforeLoading($sitePath, $siteName, $uri) {
		$_SERVER['PHP_SELF'] = $uri;
		$_SERVER['SERVER_ADDR'] ??= '127.0.0.1';
		$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
	}

	/**
	 * Determine if the incoming request is for a static file.
	 *
	 * @param string $sitePath
	 * @param string $siteName
	 * @param string $uri
	 *
	 * @return bool|string
	 */
	public function isStaticFile($sitePath, $siteName, $uri) {
		$publicPath = "$sitePath/public/" . trim($uri, '/');

		if ($this->isActualFile($publicPath)) {
			return $publicPath;
		}
		elseif (file_exists("$publicPath/index.html")) {
			return "$publicPath/index.html";
		}

		return false;
	}

	/**
	 * Get the fully resolved path to the application's front controller.
	 *
	 * @param string $sitePath
	 * @param string $siteName
	 * @param string $uri
	 *
	 * @return string|null
	 */
	public function frontControllerPath($sitePath, $siteName, $uri) {
		$docRoot = "$sitePath/public";
		$uri = rtrim($uri, '/');

		$candidates = [
			$docRoot . $uri,
			$docRoot . "$uri/index.php",
			"$docRoot/index.php",
			"$docRoot/index.html"
		];

		foreach ($candidates as $candidate) {
			if ($this->isActualFile($candidate)) {
				$_SERVER['SCRIPT_FILENAME'] = $candidate;
				$_SERVER['SCRIPT_NAME'] = str_replace("$sitePath/public", '', $candidate);
				$_SERVER['DOCUMENT_ROOT'] = "$sitePath/public";

				return $candidate;
			}
		}

		return null;
	}
}