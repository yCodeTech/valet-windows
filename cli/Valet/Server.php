<?php

namespace Valet;

class Server {
	/**
	 * @var array
	 */
	public $config;

	public function __construct($config) {
		$this->config = $config;
	}

	/**
	 * Extract $uri from $SERVER['REQUEST_URI'] variable.
	 *
	 * @param string $requestUri The request URI.
	 *
	 * @return string The extracted URI.
	 */
	public function uriFromRequestUri($requestUri) {
		return rawurldecode(
			explode('?', $requestUri)[0]
		);
	}

	/**
	 * Extract site name from HTTP host, stripping www. and supporting wildcard DNS.
	 *
	 * @param string $httpHost The HTTP host.
	 *
	 * @return string The extracted site name.
	 */
	public function siteNameFromHttpHost($httpHost) {
		$siteName = basename(
			// Filter host to support wildcard dns feature
			$this->supportWildcardDnsDomains($httpHost),
			'.'.$this->config['tld']
		);

		if (strpos($siteName, 'www.') === 0) {
			$siteName = substr($siteName, 4);
		}

		return $siteName;
	}

	/**
	 * You may use wildcard DNS provider nip.io as a tool for testing your site via
	 * an IP address.
	 * It's simple to use: First determine the IP address of your local computer
	 * (like 192.168.0.10).
	 * Then simply use http://project.your-ip.nip.io - ie: http://laravel.192.168.0.10.nip.io.
	 *
	 * @param string $domain The domain to check.
	 *
	 * @return string The domain
	 */
	public function supportWildcardDnsDomains($domain) {
		$services = [
			'.*.*.*.*.nip.io',
			'-*-*-*-*.nip.io'
		];

		if (isset($this->config['tunnel_services'])) {
			$services = array_merge($services, (array) $this->config['tunnel_services']);
		}

		$patterns = [];
		foreach ($services as $service) {
			$pattern = preg_quote($service, '#');
			$pattern = str_replace('\*', '.*', $pattern);
			$patterns[] = '(.*)' . $pattern;
		}

		$pattern = implode('|', $patterns);

		if (preg_match('#(?:' . $pattern . ')\z#u', $domain, $matches)) {
			$domain = array_pop($matches);
		}

		if (strpos($domain, ':') !== false) {
			$domain = explode(':', $domain)[0];
		}

		return $domain;
	}

	/**
	 * Determine the fully qualified path to the site.
	 * Inspects registered path directories, case-sensitive.
	 *
	 * @param string $siteName The site name.
	 *
	 * @return string|null The fully qualified path to the site, or null if not found.
	 */
	public function sitePath($siteName) {
		$valetSitePath = null;
		$domain = $this->domainFromSiteName($siteName);

		foreach ($this->config['paths'] as $path) {
			if (!is_dir($path)) {
				continue;
			}

			$handle = opendir($path);

			if ($handle === false) {
				continue;
			}

			$dirs = [];

			while (($file = readdir($handle)) !== false) {
				if (is_dir("$path/$file") && !in_array($file, ['.', '..'])) {
					$dirs[] = $file;
				}
			}

			closedir($handle);

			foreach ($dirs as $dir) {
				// Note: strtolower used below because Nginx only tells us lowercase names
				if (strtolower($dir) === $siteName) {
					// Early return when exact match for linked subdomain
					return "$path/$dir";
				}

				if (strtolower($dir) === $domain) {
					// No early return here because the foreach may still have some subdomains to
					// process with higher priority
					$valetSitePath = "$path/$dir";
				}
			}

			if ($valetSitePath) {
				return $valetSitePath;
			}
		}

		return null;
	}

	/**
	 * Extract the domain from the site name.
	 *
	 * @param string $siteName The site name.
	 *
	 * @return string The extracted domain.
	 */
	public function domainFromSiteName($siteName) {
		return array_slice(explode('.', $siteName), -1)[0];
	}

	/**
	 * Get the default site path for uncaught URLs, if it's set.
	 *
	 * @return string|null If set, default site path for uncaught urls
	 */
	public function defaultSitePath() {
		if (isset($this->config['default']) && is_string($this->config['default']) && is_dir($this->config['default'])) {
			return $this->config['default'];
		}

		return null;
	}

	/**
	 * Set the ngrok server forwarded host if it's not already set.
	 * (ngrok uses the X-Original-Host to store the forwarded hostname.)
	 */
	public function setNgrokServerForwardedHost() {
		if (isset($_SERVER['HTTP_X_ORIGINAL_HOST']) && !isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
			$_SERVER['HTTP_X_FORWARDED_HOST'] = $_SERVER['HTTP_X_ORIGINAL_HOST'];
		}
	}

	/**
	 * Is the incoming request for a static file?
	 *
	 * @param string $uri The URI of the request.
	 * @param string $valetSitePath The path to the Valet site.
	 * @param string $siteName The name of the site.
	 * @param object $valetDriver The Valet driver instance.
	 *
	 * @return string|false The path to the static file or false if not found.
	 */
	public function isRequestStaticFile($uri, $valetSitePath, $siteName, $valetDriver) {

		$isPhpFile = pathinfo($uri, PATHINFO_EXTENSION) === 'php';

		if ($uri !== '/' && !$isPhpFile && $staticFilePath = $valetDriver->isStaticFile($valetSitePath, $siteName, $uri)) {
			return $staticFilePath;
		}
		return false;
	}

	/**
	 * Show the Valet 404 "Not Found" page.
	 */
	public function show404() {
		http_response_code(404);
		require __DIR__ . '/../templates/404.html';
		exit;
	}

	/**
	 * Show directory listing or 404 if directory doesn't exist.
	 *
	 * @param string $valetSitePath The path to the Valet site.
	 * @param string $uri The URI of the request.
	 */
	public function showDirectoryListing($valetSitePath, $uri) {
		$is_root = ($uri == '/');
		$directory = $is_root ? $valetSitePath : $valetSitePath . $uri;

		if (!file_exists($directory)) {
			$this->show404();
		}

		// Sort directories at the top
		$paths = glob("$directory/*");
		usort($paths, function ($a, $b) {
			return (is_dir($a) == is_dir($b)) ? strnatcasecmp($a, $b) : (is_dir($a) ? -1 : 1);
		});

		// If the directory is a file, output its contents and exit.
		if (is_file($directory)) {
			echo "<pre>";
			echo file_get_contents($directory);
			echo "</pre>";

			exit;
		}

		// Output the HTML for the directory listing
		echo "<h1>Index of $uri</h1>";
		echo '<hr>';
		echo implode("<br>\n", array_map(function ($path) use ($uri, $is_root) {
			$file = basename($path);

			return ($is_root) ? "<a href='/$file'>/$file</a>" : "<a href='$uri/$file'>$uri/$file/</a>";
		}, $paths));

		exit;
	}

	/**
	 * Show directory listing if it's enabled or 404.
	 *
	 * @param string $valetSitePath The path to the Valet site.
	 * @param string $uri The URI of the request.
	 */
	public function showDirectoryListingOr404($valetSitePath, $uri) {
		if (isset($this->config['directory-listing']) && $this->config['directory-listing'] == 'on') {
			$this->showDirectoryListing($valetSitePath, $uri);
		}

		$this->show404();
	}

	/**
	 * Change into front controller path when executing
	 *
	 * @param string $frontControllerPath The path to the front controller.
	 */
	public function changeDir($frontControllerPath) {
		chdir(dirname($frontControllerPath));
	}
}