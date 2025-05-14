<?php

require_once __DIR__ . '/cli/drivers/require.php';
require_once __DIR__ . '/cli/Valet/Server.php';

use Valet\Server;

/**
 * Define the user's "~/.config/valet" path.
 */
define('VALET_HOME_PATH', str_replace('\\', '/', $_SERVER['HOME'] . '/.config/valet'));
define('VALET_STATIC_PREFIX', '41c270e4-5535-4daa-b23e-c269744c2f45');

/**
 * Load the Valet configuration.
 */
$valetConfig = json_decode(
	file_get_contents(VALET_HOME_PATH . '/config.json'),
	true
);

/** Load the Valet server instance. */
$server = new Server($valetConfig);

/**
 * Parse the URI and site / host for the incoming request.
 */
$uri = $server->uriFromRequestUri($_SERVER['REQUEST_URI']);
$siteName = $server->siteNameFromHttpHost($_SERVER['HTTP_HOST']);
$valetSitePath = $server->sitePath($siteName);

/**
 * Show 404 if the site path is not found.
 */
if (is_null($valetSitePath) && is_null($valetSitePath = $server->defaultSitePath())) {
	$server->show404();
}

// Resolve the site path to an absolute path.
$valetSitePath = realpath($valetSitePath);

/**
 * Find the appropriate Valet driver for the request.
 */
$valetDriver = null;

$valetDriver = ValetDriver::assign($valetSitePath, $siteName, $uri);

// Show 404 if no driver is found.
if (!$valetDriver) {
	$server->show404();
}

// Set the ngrok server forwarded host
$server->setNgrokServerForwardedHost();

/**
 * Attempt to load server environment variables.
 */
$valetDriver->loadServerEnvironmentVariables($valetSitePath, $siteName);

/**
 * Allow driver to mutate incoming URL.
 */
$uri = $valetDriver->mutateUri($uri);

/**
 * Determine if the incoming request is for a static file.
 */
$staticFilePath = $server->isRequestStaticFile($uri, $valetSitePath, $siteName, $valetDriver);

if ($staticFilePath) {
	return $valetDriver->serveStaticFile($staticFilePath, $valetSitePath, $siteName, $uri);
}

/**
 * Attempt to dispatch to a front controller.
 */
$frontControllerPath = $valetDriver->frontControllerPath($valetSitePath, $siteName, $uri);

if (!$frontControllerPath) {
	$server->showDirectoryListingOr404($valetSitePath, $uri);
}

/**
 * Change the working directory and require the front controller.
 */

// Change the working directory to the front controller's directory.
$server->changeDir($frontControllerPath);

require $frontControllerPath;