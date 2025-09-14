<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\ValetDriver;

class Magento2ValetDriver extends ValetDriver {
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
		return file_exists("{$sitePath}/bin/magento") && file_exists("{$sitePath}/pub/index.php");
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
		$uri = preg_replace('/^\/static(\/version[\d]+)/', '/static', $uri);

		if (file_exists($staticFilePath = "$sitePath/pub$uri")) {
			return $staticFilePath;
		}

		if (strpos($uri, '/static/') === 0) {
			$_GET['resource'] = preg_replace('#static/#', '', $uri, 1);
			include "$sitePath/pub/static.php";
			exit;
		}

		if (strpos($uri, '/media/') === 0) {
			include "$sitePath/pub/get.php";
			exit;
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
		$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
		$_SERVER['DOCUMENT_ROOT'] = $sitePath;

		return $sitePath . '/pub/index.php';
	}
}