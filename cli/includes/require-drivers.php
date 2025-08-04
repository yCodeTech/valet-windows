<?php

$includesDir = __DIR__;

$valetDriversDir = "$includesDir/../Valet/Drivers";

require_once "$valetDriversDir/ValetDriver.php";
require_once "$includesDir/legacy_drivers/ValetDriver.php";


foreach (scandir($valetDriversDir) as $file) {
	$path = "$valetDriversDir/$file";
	if (substr($file, 0, 1) !== '.' && ! is_dir($path)) {
		require_once $path;
	}
}

// Require legacy drivers.
// DEPRECATED: The legacy drivers are deprecated as of v3.2.0 and therefore so is this code.
$legacyDriversDir = "$includesDir/legacy_drivers";

foreach (scandir($legacyDriversDir) as $file) {
	if (substr($file, 0, 1) !== '.') {
		require_once "$legacyDriversDir/$file";
	}
}