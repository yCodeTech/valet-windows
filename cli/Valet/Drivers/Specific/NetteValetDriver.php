<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\ValetDriver;

class NetteValetDriver extends ValetDriver {
	/**
	 * Determine if the driver serves the request.
	 *
	 * @param string $sitePath
	 * @param string $siteName
	 * @param string $uri
	 *
	 * @return bool
	 */
	public function serves($sitePath, $siteName, $uri): bool {
		return file_exists("{$sitePath}/www/index.php") &&
		file_exists("{$sitePath}/www/.htaccess") &&
		file_exists("{$sitePath}/config/common.neon") &&
		file_exists("{$sitePath}/config/services.neon");
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
		$_SERVER['DOCUMENT_ROOT'] = "{$sitePath}/www";
		$_SERVER['SCRIPT_FILENAME'] = "{$sitePath}/www/index.php";
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		$_SERVER['PHP_SELF'] = '/index.php';
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
		if ($this->isActualFile($staticFilePath = "{$sitePath}/www/{$uri}")) {
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
		return "{$sitePath}/www/index.php";
	}
}