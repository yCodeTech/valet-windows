<?php

/**
 * Check the system's compatibility with Valet.
 */
$inTestingEnvironment = strpos($_SERVER['SCRIPT_NAME'], 'phpunit') !== false;

if (PHP_OS !== 'WINNT' && ! $inTestingEnvironment) {
    echo 'Valet for Windows only supports the Windows operating system.'.PHP_EOL;

    exit(1);
}

if (version_compare(PHP_VERSION, '7.3.0', '<')) {
    echo 'Valet requires PHP 7.3 or later.';

    exit(1);
}
