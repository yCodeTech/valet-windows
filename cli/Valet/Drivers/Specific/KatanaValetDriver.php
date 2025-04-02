<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\BasicValetDriver;

class KatanaValetDriver extends BasicValetDriver {
	/**
	 * Mutate the incoming URI.
	 *
	 * @param  string  $uri
	 * @return string
	 */
	public function mutateUri($uri) {
		return rtrim('/public' . $uri, '/');
	}

	/**
	 * Determine if the driver serves the request.
	 *
	 * @param  string  $sitePath
	 * @param  string  $siteName
	 * @param  string  $uri
	 * @return boolean
	 */
	public function serves($sitePath, $siteName, $uri) {
		return file_exists($sitePath . '/katana');
	}
}