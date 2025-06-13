<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\BasicValetDriver;

class JoomlaValetDriver extends BasicValetDriver {
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
		return is_dir("{$sitePath}/libraries/joomla");
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
}
