<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\BasicValetDriver;

class RadicleValetDriver extends BasicValetDriver {
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
		return file_exists("{$sitePath}/public/content/mu-plugins/bedrock-autoloader.php") &&
		file_exists("{$sitePath}/public/wp-config.php") &&
		file_exists("{$sitePath}/bedrock/application.php");
	}

	/**
	 * Take any steps necessary before loading the front controller for this driver.
	 *
	 * @param string $sitePath
	 * @param string $siteName
	 * @param string $uri
	 * @return void
	 */
	public function beforeLoading($sitePath, $siteName, $uri) {
		$_SERVER['PHP_SELF'] = $uri;
	}

	/**
	 * Determine if the incoming request is for a static file.
	 *
	 * @param string $sitePath
	 * @param string $siteName
	 * @param string $uri
	 *
	 * @return string|false
	 */
	public function isStaticFile($sitePath, $siteName, $uri) {
		$staticFilePath = "{$sitePath}/public{$uri}";
		if ($this->isActualFile($staticFilePath)) {
			return $staticFilePath;
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
	 * @return string
	 */
	public function frontControllerPath($sitePath, $siteName, $uri) {
		if (strpos($uri, '/wp/') === 0) {
			return is_dir("{$sitePath}/public{$uri}")
			? "{$sitePath}/public" . $this->forceTrailingSlash($uri) . '/index.php'
			: "{$sitePath}/public{$uri}";
		}

		return "{$sitePath}/public/index.php";
	}

	/**
	 * Redirect to URI with trailing slash.
	 *
	 * @param string $uri
	 *
	 * @return string
	 */
	private function forceTrailingSlash($uri) {
		if (substr($uri, -1 * strlen('/wp/wp-admin')) == '/wp/wp-admin') {
			header("Location: {$uri}/");
			exit;
		}

		return $uri;
	}
}