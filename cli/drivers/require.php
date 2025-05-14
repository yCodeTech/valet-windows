<?php

$dir = __DIR__;

require_once "$dir/ValetDriver.php";

foreach (scandir($dir) as $file) {
	$path = "$dir/$file";
	if (substr($file, 0, 1) !== '.' && ! is_dir($path)) {
		require_once $path;
	}
}