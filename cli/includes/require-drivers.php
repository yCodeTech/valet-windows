<?php

$includesDir = __DIR__;

$valetDriversDir = "$includesDir/../Valet/Drivers";

require_once "$valetDriversDir/ValetDriver.php";

foreach (scandir($valetDriversDir) as $file) {
	$path = "$valetDriversDir/$file";
	if (substr($file, 0, 1) !== '.' && ! is_dir($path)) {
		require_once $path;
	}
}

/**
 * Require legacy drivers
 */

// DEPRECATED: The legacy drivers are deprecated as of v3.3.0 and therefore so is this code, and will be removed in 4.0.0.

// Require specific drivers.

// For the legacy drivers to work, we need to require the specific drivers first.
// This is because the legacy drivers extend the specific drivers.
// When the legacy drivers are removed in 4.0.0, this will no longer be necessary,
// as the specific drivers will just work via PSR-4 autoloading.

require_once "$includesDir/legacy_drivers/ValetDriver.php";

$specificDriversDir = "$valetDriversDir/Specific";

foreach (scandir($specificDriversDir) as $file) {
	$path = "$specificDriversDir/$file";
	if (substr($file, 0, 1) !== '.' && ! is_dir($path)) {
		require_once $path;
	}
}

// Require legacy drivers.
$legacyDriversDir = "$includesDir/legacy";

foreach (scandir($legacyDriversDir) as $file) {
	if (substr($file, 0, 1) !== '.') {
		require_once "$legacyDriversDir/$file";
	}
}
