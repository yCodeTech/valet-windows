<?php

namespace Valet\Drivers\Specific;

use Valet\Drivers\BasicValetDriver;

class SculpinValetDriver extends BasicValetDriver {
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
		return $this->isModernSculpinProject($sitePath)
		|| $this->isLegacySculpinProject($sitePath);
	}

	/**
	 * Determine if the project is a modern Sculpin project.
	 *
	 * @param string $sitePath
	 *
	 * @return bool
	 */
	private function isModernSculpinProject($sitePath) {
		return is_dir("{$sitePath}/source")
		&& is_dir("{$sitePath}/output_dev")
		&& $this->composerRequires($sitePath, 'sculpin/sculpin');
	}

	/**
	 * Determine if the project is a legacy Sculpin project.
	 *
	 * @param string $sitePath
	 *
	 * @return bool
	 */
	private function isLegacySculpinProject($sitePath) {
		return is_dir("{$sitePath}/.sculpin");
	}

	/**
	 * Mutate the incoming URI.
	 *
	 * @param string $uri
	 *
	 * @return string
	 */
	public function mutateUri($uri) {
		return rtrim("/output_dev{$uri}", '/');
	}
}
