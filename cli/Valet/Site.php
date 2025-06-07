<?php

namespace Valet;

use DomainException;
use phpseclib3\Crypt\RSA;
use phpseclib3\File\X509;

class Site {
	protected $config;
	protected $cli;
	protected $files;

	/**
	 * Create a new Site instance.
	 *
	 * @param Configuration $config
	 * @param CommandLine $cli
	 * @param Filesystem $files
	 * @return void
	 */
	public function __construct(Configuration $config, CommandLine $cli, Filesystem $files) {
		$this->cli = $cli;
		$this->files = $files;
		$this->config = $config;
	}

	/**
	 * Get the name of the site.
	 *
	 * @param string|null $name
	 * @return string
	 */
	private function getRealSiteName($name) {
		if (!is_null($name)) {
			return $name;
		}

		if (is_string($link = $this->getLinkNameByCurrentDir())) {
			return $link;
		}

		return basename(getcwd());
	}

	/**
	 * Get link name based on the current directory.
	 *
	 * @return null|string
	 */
	public function getLinkNameByCurrentDir() {
		$count = count($links = $this->links()->where('path', getcwd()));

		if ($count == 1) {
			return $links->shift()['site'];
		}

		if ($count > 1) {
			throw new DomainException("There are {$count} links ({$links->implode("site", ", ")}) related to the current working directory, you will need to specify which site to unlink, eg. valet unlink [name].");
		}
	}

	/**
	 * Get the real hostname for the given path, checking links.
	 *
	 * @param string $path
	 * @return string|null
	 */
	public function host($path) {
		foreach ($this->files->scandir($this->sitesPath()) as $link) {
			if ($resolved = realpath($this->sitesPath($link)) === $path) {
				return $link;
			}
		}

		return basename($path);
	}

	/**
	 * Link the current working directory with the given name.
	 *
	 * @param string $target
	 * @param string $link
	 * @return string
	 */
	public function link($target, $link) {
		$this->files->ensureDirExists(
			$linkPath = $this->sitesPath(),
			user()
		);

		$this->config->prependPath($linkPath);

		$this->files->symlink($target, $linkPath . '/' . $link);

		return $linkPath . '/' . $link;
	}

	/**
	 * Unlink the given symbolic link.
	 *
	 * @param string $name
	 * @return void
	 */
	public function unlink($name) {
		$name = $this->getRealSiteName($name);

		if ($this->files->exists($path = $this->sitesPath($name))) {
			$this->files->unlink($path);
		}
	}

	/**
	 * Remove all broken symbolic links.
	 *
	 * @return void
	 */
	public function pruneLinks() {
		$this->files->ensureDirExists($this->sitesPath(), user());

		$this->files->removeBrokenLinksAt($this->sitesPath());
	}

	/**
	 * Pretty print out all links in Valet.
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function links() {
		$certsPath = $this->certificatesPath();

		$this->files->ensureDirExists($certsPath, user());

		$certs = $this->getCertificates($certsPath);

		return $this->getSites($this->sitesPath(), $certs);
	}

	/**
	 * Pretty print out all parked links in Valet.
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function parked() {
		$certs = $this->getCertificates();

		$links = $this->getSites($this->sitesPath(), $certs);

		$config = $this->config->read();
		$parkedLinks = collect();
		foreach (array_reverse($config['paths']) as $path) {
			if ($path === $this->sitesPath()) {
				continue;
			}

			// Only merge on the parked sites that don't interfere with the linked sites
			$sites = $this->getSites($path, $certs)->filter(function ($site, $key) use ($links) {
				return !$links->has($key);
			});

			$parkedLinks = $parkedLinks->merge($sites);
		}

		return $parkedLinks;
	}

	public function getSiteConfigFileContents($site, $suffix = null) {
		$config = $this->config->read();
		$suffix = $suffix ?: '.' . $config['tld'];
		$file = str_replace($suffix, '', $site) . $suffix;

		return $this->files->exists($this->nginxPath($file)) ? $this->files->get($this->nginxPath($file)) : null;
	}

	/**
	 * Get all certificates from config folder.
	 *
	 * @param string $path
	 * @return \Illuminate\Support\Collection
	 */
	public function getCertificates($path = null) {
		$path = $path ?: $this->certificatesPath();

		$this->files->ensureDirExists($path, user());

		$config = $this->config->read();

		return collect($this->files->scandir($path))->filter(function ($value, $key) {
			return str_ends_with($value, '.crt');
		})->map(function ($cert) use ($config) {
			$certWithoutSuffix = substr($cert, 0, -4);
			$trimToString = '.';

			// If we have the cert ending in our tld strip that tld specifically
			// if not then just strip the last segment for backwards compatibility.
			if (str_ends_with($certWithoutSuffix, $config['tld'])) {
				$trimToString .= $config['tld'];
			}

			return substr($certWithoutSuffix, 0, strrpos($certWithoutSuffix, $trimToString));
		})->flip();
	}

	/**
	 * Create a site object (aka associative array) using Laravel Collection with site details, like name, php version, etc.
	 *
	 * @param string $path Either the path to the parked sites directory or the symbolic link sites directory.
	 * @return \Illuminate\Support\Collection
	 */
	public function createSiteObject($path) {
		return collect($this->files->scandir($path))->mapWithKeys(function ($site) use ($path) {
			$sitePath = $path . '/' . $site;

			if ($this->files->isLink($sitePath)) {
				$realPath = $this->files->readLink($sitePath);
			}
			else {
				$realPath = $this->files->realpath($sitePath);
			}

				return [$site => $realPath];
		})->filter(function ($path) {
			return $this->files->isDir($path);
		});
	}

	/**
	 * Get list of sites and return them formatted
	 * Will work for symlink and normal site paths.
	 *
	 * @param string $path
	 * @param \Illuminate\Support\Collection $certs
	 * @return \Illuminate\Support\Collection
	 */
	public function getSites($path, $certs) {
		$config = $this->config->read();

		$this->files->ensureDirExists($path, user());

		$links = $this->createSiteObject($this->sitesPath())->flatMap(function ($item, $key) {
			return ["site" => $key, "path" => $item];
		});

		$parked = $this->createSiteObject($path);

		return $parked->map(function ($path, $site) use ($certs, $config, $links) {

			$alias = ""; // Default variable string.
			$secured = $certs->has($site);
			$url = ($secured ? 'https' : 'http') . '://' . $site . '.' . $config['tld'];
			$aliasUrl = ""; // Default variable string.

			// Get the PHP version.
			$phpVersion = $this->getPhpVersion($site);

			// If the path equals the linked site path...
			if ($path === $links->get("path")) {
				// Set the alias to the linked site custom name.
				$alias = $links->get("site");
				// Set the alias url.
				$aliasUrl = ($secured ? 'https' : 'http') . '://' . $links->get("site") . '.' . $config['tld'];
				$phpVersion = $this->getPhpVersion($links["site"], true);
			}

			return [
				'site' => $site,
				'alias' => $alias,
				'secured' => $secured ? 'X' : '',
				'php' => $phpVersion,
				'url' => $url,
				'aliasUrl' => $aliasUrl,
				'path' => $path
			];
		});
	}

	/**
	 * Get the PHP version for the given site.
	 * @param string $site
	 * @param bool $symlink Are we getting the version for a symbolic link site? Default `false`.
	 * @return string PHP version
	 */
	public function getPhpVersion($site, $symlink = false) {
		$tld = $this->config->get('tld');

		// Get the default version.
		$defaultVersion = $this->config->get('default_php');
		// Get the isolated PHP version of the sites if set.
		$phpVersion = $this->customPhpVersion($site . '.' . $tld);

		// If symlink is true
		if ($symlink === true) {
			// Get the isolated PHP version of the linked site if set.
			$phpVersion = $this->customPhpVersion($site . '.' . $tld);
		}

		// If either the parked or symbolic link site isolated phpVersion is empty,
		// then it must be using the default version.
		if (empty($phpVersion)) {
			return "$defaultVersion (default)";
		}
		else {
			return "<info>$phpVersion (isolated)</info>";
		}
	}

	/**
	 * Extract PHP version of exising nginx config.
	 *
	 * @param string $url
	 * @return string|void
	 */
	public function customPhpVersion($url) {
		if ($this->files->exists($this->nginxPath($url))) {
			$siteConf = $this->files->get($this->nginxPath($url));

			if (str_starts_with($siteConf, '# Valet isolated PHP version')) {
				$firstLine = explode(PHP_EOL, $siteConf)[0];

				return trim(str_replace('# Valet isolated PHP version : ', '', $firstLine));
			}
		}
	}

	/**
	 * Determines which PHP version the current working directory is using.
	 *
	 * @param string $cwd The current working directory (cwd)
	 * @return array|null [ "site" => [sitename], "php" => [PHP version] ]
	 */
	public function whichPhp($cwd) {
		$currentSite = $this->parked()->merge($this->links())->filter(function ($site, $key) use ($cwd) {
			if ($key === $cwd) return $site;
		});

		foreach ($currentSite as $value) {
			foreach ($value as $key => $val) {
				$site = $cwd;
				$site .= !empty($value["alias"]) ? " (alias: {$value["alias"]})" : "";

				if ($key === "php") {
					// Extract PHP version from the ansi string, eg. <info>7.4.9 (isolated)</info>
					// so we only get the raw version string.
					preg_match("([0-9.]+)", $val, $phpVersion);

					return [
						"site" => $site,
						"php" => $val,
						"phpVersion" => $phpVersion[0]
					];
				}
			}
		}
	}

	/**
	 * Isolate a given directory to use a specific version of PHP.
	 *
	 * @param string $phpVersion
	 * @param string $directory
	 * @return void
	 */
	public function isolate($phpVersion, $directory) {
		$site = $this->getSiteUrl($directory);

		$php = $this->config->getPhpByVersion($phpVersion);

		$this->installSiteConfig($site, $php['version']);

		info("The site [$site] is now using $phpVersion.");
	}

	/**
	 * Remove PHP version isolation for a given directory.
	 * @param bool $isOldTLD Is it the old TLD? Only used in the `reisolateForNewTld` function. Default: `false`.
	 */
	public function unisolate($directory, $isOldTLD = false) {
		$site = $isOldTLD ? $directory : $this->getSiteUrl($directory);
		// Make sure the isOldTLD is false AND the site is isolated, otherwise stop executing.
		if (!$isOldTLD && !$this->isIsolated($site)) {
			error("Can't unisolate {$site} because the site is not isolated.");
			return false;
		}


		// If a site has an SSL/TLS certificate, we need to keep its custom config file,
		// but we can just re-generate it.
		if ($this->files->exists($this->certificatesPath($site, 'crt'))) {
			$siteConf = $this->buildSecureNginxServer($site);
			$this->files->putAsUser($this->nginxPath($site), $siteConf);
		}
		else {
			// When site doesn't have SSL/TLS, we can remove the custom nginx config file to remove isolation
			$this->files->unlink($this->nginxPath($site));
		}

		info(sprintf('The site [%s] is now using the default PHP version.', $site));
	}

	/**
	 * Get list of isolated sites.
	 * @param string $oldTld The old TLD. Only used by `reisolateForNewTld()` when changing the TLD.
	 */
	public function isolated($oldTld = null) {
		$dir = $this->nginxPath();
		$tld = $oldTld ?: $this->config->read()['tld'];

		$isolated = collect($this->files->scandir($dir))->filter(function ($site, $key) use ($tld) {
			// keep sites that match our TLD
			return str_ends_with($site, ".$tld.conf");
		})->map(function ($site, $key) use ($tld) {
			return [
				"site" => str_replace(".$tld.conf", '', $site),
				"php" => $this->customPhpVersion($site)
			];
		})->whereNotIn('php', '');

		return $isolated;
	}

	/**
	 * Determine if site is isolated.
	 * @param string $site
	 * @return bool
	 */
	public function isIsolated($site) {
		$isolated = $this->isolated()->map(function ($arr, $key) {
			return ["site" => $arr["site"]];
		})->flatten()->all();

		return in_array(explode(".", $site)[0], $isolated);
	}

	/**
	 * Reisolate all currently isolated sites with the new tld.
	 *
	 * @param string $oldTld
	 * @param string $tld
	 * @return void
	 */
	public function reisolateForNewTld($oldTld, $tld) {
		$isolated = $this->isolated($oldTld)->pluck("site")->all();

		foreach ($isolated as $url) {
			$oldUrl = $url . '.' . $oldTld;
			$newUrl = $url . '.' . $tld;
			// Only used to determine if it's actually an isolated site.
			$siteConf = $this->getSiteConfigFileContents($url, '.' . $oldTld);

			// If the site conf is not empty, and it has the keywords isolated,
			// then it's an isolated site, so we can unisolate the old TLD,
			// and reisolate it with the new TLD.
			if (!empty($siteConf) && strpos($siteConf, '# Valet isolated') === 0) {
				$phpVersion = $this->customPhpVersion($oldUrl);
				$this->unisolate($oldUrl, true);
				$this->isolate($phpVersion, $newUrl);
			}
		}
	}

	/**
	 * Get the site URL from a directory if it's a valid Valet site.
	 *
	 * @param string $directory
	 * @return string|false
	 */
	public function getSiteUrl($directory) {
		$tld = $this->config->read()['tld'];
		$txt = "";

		// If user supplied a dot as the current dir's site `--site=.`
		// or if directory is null,
		// then find and use the current working directory site.
		if ($directory == '.' || $directory == './' || $directory == null) {
			$directory = $this->host(getcwd());
			$txt = "The current working directory";
		}

		// Remove .tld from sitename if it was provided
		$directory = str_replace('.' . $tld, '', $directory);

		if (!$this->parked()->merge($this->links())->where('site', $directory)->count() > 0) {
			// Invalid directory provided
			throw new DomainException("$txt '$directory' is an invalid site.");
		}

		return $directory . '.' . $tld;
	}

	/**
	 * Secure the given host with TLS.
	 *
	 * @param string $url
	 * @param string $siteConf pregenerated Nginx config file contents
	 * @return void
	 */
	public function secure($url, $siteConf = null) {
		// Extract in order to later preserve custom PHP version config when securing
		$phpVersion = $this->customPhpVersion($url);
		$this->unsecure($url);

		$this->files->ensureDirExists($this->caPath(), user());

		$this->files->ensureDirExists($this->certificatesPath(), user());

		$this->files->ensureDirExists($this->nginxPath(), user());

		$this->createCa();

		$this->createCertificate($url);

		$siteConf = $this->buildSecureNginxServer($url, $siteConf);

		// If the user had isolated the PHP version for this site, swap out the PHP version
		if ($phpVersion) {
			$php = $this->config->getPhpByVersion($phpVersion);
			$siteConf = $this->replacePhpVersionInSiteConf($siteConf, $php['port'], $phpVersion);
		}

		$this->files->putAsUser(
			$this->nginxPath($url),
			$this->buildSecureNginxServer($url, $siteConf)
		);
	}

	/**
	 * Unsecure the given URL so that it will use HTTP again.
	 *
	 * @param string $url
	 * @return void
	 */
	public function unsecure($url) {
		// Extract in order to later preserve custom PHP version config when unsecuring. Example output: "8.1.2"
		$phpVersion = $this->customPhpVersion($url);

		if ($this->files->exists($this->certificatesPath($url, 'crt'))) {
			$this->files->unlink($this->nginxPath($url));

			$this->files->unlink($this->certificatesPath($url, 'key'));
			$this->files->unlink($this->certificatesPath($url, 'csr'));
			$this->files->unlink($this->certificatesPath($url, 'crt'));
		}

		$this->cli->run(sprintf('cmd "/C certutil -user -delstore "CA" "%s""', $url));

		$this->cli->run(sprintf('cmd "/C certutil -user -delstore "Root" "%s""', $url));

		// If the user had isolated the PHP version for this site, swap out .sock file
		if ($phpVersion) {
			$this->installSiteConfig($url, $phpVersion);
		}
	}

	/**
	 * Unsecure all URLs so that they will use HTTP again.
	 * @param bool $fromUninstall Determine if the function call was from the
	 * `uninstall` command or not. Default: `false`
	 */
	public function unsecureAll($fromUninstall = false) {
		$tld = $this->config->read()['tld'];

		$secured = $this->parked()
			->merge($this->links())
			->sort()
			->where('secured', 'X');

		// If there are no secured sites AND
		// the function call didn't come from the `uninstall` command,
		// then we can send an output and exit the script.
		if ($secured->count() === 0 && !$fromUninstall) {
			info("No sites to unsecure. You may list all servable sites or links by running <bg=magenta> valet parked </> or <bg=magenta> valet links </>.");
			// Prevents further scripts from running, but without sending out an error exception.
			exit();
		}

		info('Attempting to unsecure the following sites:');
		table(default_table_headers(), $secured->toArray());

		foreach ($secured->pluck('site') as $url) {
			$this->unsecure($url . '.' . $tld);
		}

		$remaining = $this->parked()
			->merge($this->links())
			->sort()
			->where('secured', 'X');
		if ($remaining->count() > 0) {
			warning('We were not successful in unsecuring the following sites:');
			table(default_table_headers(), $remaining->toArray());
			return;
		}
		info('Unsecured all sites.');
	}

	/**
	 * Get all of the URLs that are currently secured.
	 *
	 * @return array
	 */
	public function secured() {
		return collect($this->files->scandir($this->certificatesPath()))->map(function ($file) {
			return str_replace(['.key', '.csr', '.crt', '.conf'], '', $file);
		})->unique()->values()->all();
	}

	/**
	 * Determine if site is secured.
	 * @param string $site
	 * @return bool
	 */
	public function isSecured($site) {
		$tld = $this->config->read()['tld'];

		// Remove .tld from sitename if it was provided, to avoid double .tld (.tld.tld)
		$site = str_replace('.' . $tld, '', $site);

		return in_array($site . '.' . $tld, $this->secured());
	}

	/**
	 * Resecure all currently secured sites with a fresh tld.
	 *
	 * @param string $oldTld
	 * @param string $tld
	 * @return void
	 */
	public function resecureForNewTld($oldTld, $tld) {
		if (!$this->files->exists($this->certificatesPath())) {
			return;
		}

		$secured = $this->secured();

		foreach ($secured as $url) {
			$newUrl = str_replace('.' . $oldTld, '.' . $tld, $url);
			$siteConf = $this->getSiteConfigFileContents($url, '.' . $oldTld);

			if (!empty($siteConf) && strpos($siteConf, '# valet stub: secure.proxy.valet.conf') === 0) {
				// proxy config
				$this->unsecure($url);
				$this->secure($newUrl, $this->replaceOldDomainWithNew($siteConf, $url, $newUrl));
			}
			else {
				// normal config
				$this->unsecure($url);
				$this->secure($newUrl);
			}
		}
	}

	/**
	 * Parse Nginx site config file contents to swap old domain to new.
	 *
	 * @param string $siteConf Nginx site config content
	 * @param string $old Old domain
	 * @param string $new New domain
	 * @return string
	 */
	public function replaceOldDomainWithNew($siteConf, $old, $new) {
		$lookups = [];
		$lookups[] = '~server_name .*;~';
		$lookups[] = '~error_log .*;~';
		$lookups[] = '~ssl_certificate_key .*;~';
		$lookups[] = '~ssl_certificate .*;~';

		foreach ($lookups as $lookup) {
			preg_match($lookup, $siteConf, $matches);
			foreach ($matches as $match) {
				$replaced = str_replace($old, $new, $match);
				$siteConf = str_replace($match, $replaced, $siteConf);
			}
		}

		return $siteConf;
	}

	/**
	 * Get the port of the given host.
	 *
	 * @param string $url
	 * @return int
	 */
	public function port(string $url): int {
		if ($this->files->exists($path = $this->nginxPath($url))) {
			if (strpos($this->files->get($path), '443') !== false) {
				return 443;
			}
		}

		return 80;
	}

	/**
	 * If CA and root certificates are nonexistent, create them and trust the root cert.
	 *
	 * @return void
	 */
	public function createCa() {
		$caPemPath = $this->caPath('LaravelValetCASelfSigned.crt');
		$caKeyPath = $this->caPath('LaravelValetCASelfSigned.key');

		if ($this->files->exists($caKeyPath) && $this->files->exists($caPemPath)) {
			return;
		}

		$oName = 'Laravel Valet Windows 3 CA Self Signed Organization';
		$cName = 'Laravel Valet Windows 3 CA Self Signed CN';

		if ($this->files->exists($caKeyPath)) {
			$this->files->unlink($caKeyPath);
		}
		if ($this->files->exists($caPemPath)) {
			$this->files->unlink($caPemPath);
		}

		$this->cli->runOrExit(
			sprintf('cmd "/C certutil -user -delstore "Root" "%s""', $cName),
			function ($code, $output) {
				error("Failed to delete certificate: $output", true);
			}
		);

		$CAPrivKey = RSA::createKey()->withPadding(RSA::ENCRYPTION_PKCS1 | RSA::SIGNATURE_PKCS1);
		$CAPubKey = $CAPrivKey->getPublicKey();

		$CASubject = new X509();
		$CASubject->setDNProp('emailaddress', 'rootcertificate@laravel.valet');
		$CASubject->setDNProp('ou', 'Developers');
		$CASubject->setDNProp('cn', $cName);
		$CASubject->setDNProp('o', $oName);
		$CASubject->setPublicKey($CAPubKey);

		$CAIssuer = new X509();
		$CAIssuer->setPrivateKey($CAPrivKey);
		$CAIssuer->setDN($CASubject->getDN());

		$x509 = new X509();
		$x509->makeCA();
		$result = $x509->sign($CAIssuer, $CASubject);

		$CAPem = $x509->saveX509($result);

		$this->files->putAsUser($caPemPath, $CAPem);
		$this->files->putAsUser($caKeyPath, $CAPrivKey);

		$this->trustCa($caPemPath);
	}

	/**
	 * Create and trust a certificate for the given URL.
	 *
	 * @param string $url
	 * @return void
	 */
	public function createCertificate($url) {
		$keyPath = $this->certificatesPath($url, 'key');
		$csrPath = $this->certificatesPath($url, 'csr');
		$crtPath = $this->certificatesPath($url, 'crt');
		$caPemPath = $this->caPath('LaravelValetCASelfSigned.crt');
		$caKeyPath = $this->caPath('LaravelValetCASelfSigned.key');

		$this->createPrivateKey($keyPath);
		$this->createSigningRequest($url, $keyPath, $csrPath);
		$this->createSignedCertificate($keyPath, $csrPath, $crtPath, $caPemPath, $caKeyPath);

		$this->trustCertificate($crtPath);
	}

	/**
	 * Create the private key for the TLS certificate.
	 *
	 * @param string $keyPath
	 * @return void
	 */
	public function createPrivateKey(string $keyPath) {
		/** @var \phpseclib3\Crypt\RSA\PrivateKey */
		$key = RSA::createKey();

		$this->files->putAsUser($keyPath, $key->toString('PKCS1'));
	}

	/**
	 * Create the signing request for the TLS certificate.
	 *
	 * @param string $url
	 * @param string $keyPath
	 * @param string $csrPath
	 * @return void
	 */
	public function createSigningRequest(string $url, string $keyPath, string $csrPath) {
		/** @var \phpseclib3\Crypt\RSA\PrivateKey */
		$privKey = RSA::load($this->files->get($keyPath));

		$x509 = new X509();
		$x509->setPrivateKey($privKey);
		$x509->setDNProp('commonname', $url);

		$x509->loadCSR($x509->saveCSR($x509->signCSR()));

		$x509->setExtension('id-ce-subjectAltName', [
			['dNSName' => $url],
			['dNSName' => "*.$url"]
		]);

		$x509->setExtension('id-ce-keyUsage', [
			'digitalSignature',
			'nonRepudiation',
			'keyEncipherment'
		]);

		$csr = $x509->saveCSR($x509->signCSR());

		$this->files->putAsUser($csrPath, $csr);
	}

	/**
	 * Create the signed TLS certificate.
	 *
	 * @param string $keyPath
	 * @param string $csrPath
	 * @param string $crtPath
	 * @param string $caPemPath
	 * @param string $caKeyPath
	 * @return void
	 */
	public function createSignedCertificate(string $keyPath, string $csrPath, string $crtPath, string $caPemPath, string $caKeyPath) {
		/** @var \phpseclib3\Crypt\RSA\PrivateKey */
		$privKey = RSA::load($this->files->get($keyPath));
		$privKey = $privKey->withPadding(RSA::SIGNATURE_PKCS1);

		$subject = new X509();
		$subject->loadCSR($this->files->get($csrPath));
		$subject->setPublicKey($privKey->getPublicKey());
		$subject->setDNProp('emailaddress', 'valet');
		$subject->setDNProp('ou', 'Laravel Valet Windows 3');
		$subject->setDNProp('l', 'Laravel Valet Windows 3');
		$subject->setDNProp('o', 'Laravel Valet Windows 3');
		$subject->setDNProp('st', 'MN');
		$subject->setDNProp('c', 'US');

		$ca = new X509();
		$ca->loadX509($this->files->get($caPemPath));

		$caPrivKey = RSA::load($this->files->get($caKeyPath))->withPadding(RSA::ENCRYPTION_PKCS1 | RSA::SIGNATURE_PKCS1);

		$issuer = new X509();
		$issuer->setPrivateKey($caPrivKey);
		$issuer->setDN($ca->getIssuerDN());

		$x509 = new X509();
		$x509->makeCA();
		$x509->setStartDate('-1 day');

		$result = $x509->sign($issuer, $subject);
		$certificate = $x509->saveX509($result);

		$this->files->putAsUser($crtPath, $certificate);
	}

	/**
	 * Trust the given root certificate file in the Windows Certmgr.
	 *
	 * @param string $pemPath
	 * @return void
	 */
	public function trustCa($caPemPath) {
		$this->cli->runOrExit(
			sprintf('cmd "/C certutil -user -addstore "Root" "%s""', $caPemPath),
			function ($code, $output) {
				error("Failed to trust certificate: $output", true);
			}
		);
	}

	/**
	 * Trust the given certificate file in the Windows Certmgr.
	 *
	 * @param string $crtPath
	 * @return void
	 */
	public function trustCertificate(string $crtPath) {
		$this->cli->runOrExit(
			sprintf('cmd "/C certutil -user -addstore "CA" "%s""', $crtPath),
			function ($code, $output) {
				error("Failed to trust certificate: $output", true);
			}
		);
	}

	/**
	 * Untrust all certificates.
	 *
	 * @return void
	 */
	public function untrustCertificates() {
		$secured = $this->parked()
			->merge($this->links())
			->sort()
			->where('secured', 'X');

		if ($secured->isEmpty()) {
			return;
		}

		$tld = $this->config->get('tld');

		foreach ($secured->pluck('site') as $site) {
			$this->cli->run(sprintf('cmd "/C certutil -user -delstore "CA" "%s""', $site . '.' . $tld));

			$this->cli->run(sprintf('cmd "/C certutil -user -delstore "Root" "%s""', $site . '.' . $tld));
		}
	}

	/**
	 * Build the Nginx server configuration for the given Valet site.
	 *
	 * @param string $valetSite
	 * @param string $phpVersion
	 * @return string|null
	 */
	public function installSiteConfig($valetSite, $phpVersion) {
		$phpVersion = $phpVersion ? $phpVersion : $this->config->get('default_php');

		$php = $this->config->getPhpByVersion($phpVersion);

		if ($this->files->exists($this->nginxPath($valetSite))) {
			$siteConf = $this->files->get($this->nginxPath($valetSite));
		}
		else {
			$siteConf = $this->files->getStub('unsecure.valet.conf');
			$siteConf = str_replace(
				['VALET_HOME_PATH', 'VALET_SERVER_PATH', 'VALET_STATIC_PREFIX', 'VALET_SITE', 'HOME_PATH'],
				[$this->valetHomePath(), VALET_SERVER_PATH, VALET_STATIC_PREFIX, $valetSite, $_SERVER['HOME']],
				$siteConf
			);
		}

		$siteConf = $this->replacePhpVersionInSiteConf($siteConf, $php['port'], $php['version']);

		$this->files->putAsUser($this->nginxPath($valetSite), $siteConf);
	}

	/**
	 * Build the TLS secured Nginx server for the given URL.
	 *
	 * @param string $url
	 * @param string $siteConf (optional) Nginx site config file content
	 * @return string
	 */
	public function buildSecureNginxServer($url, $siteConf = null) {
		if ($siteConf === null) {
			$siteConf = $this->files->getStub('secure.valet.conf');
		}

		$path = $this->certificatesPath();

		return str_replace(
			['VALET_HOME_PATH', 'VALET_SERVER_PATH', 'VALET_STATIC_PREFIX', 'VALET_SITE', 'VALET_CERT', 'VALET_KEY', 'HOME_PATH'],
			[$this->valetHomePath(), VALET_SERVER_PATH, VALET_STATIC_PREFIX, $url, $path . '/' . $url . '.crt', $path . '/' . $url . '.key', $_SERVER['HOME']],
			$siteConf
		);
	}

	/**
	 * Replace PHP version and port in an Nginx site configuration file contents.
	 *
	 * @param string $siteConf
	 * @param string $phpPort
	 */
	public function replacePhpVersionInSiteConf($siteConf, $phpPort, $phpVersion = null) {
		$siteConf = str_replace('127.0.0.1:$valet_php_port;', "127.0.0.1:{$phpPort};", $siteConf);

		// Remove `Valet isolated PHP version` line from config
		$siteConf = preg_replace('/# Valet isolated PHP version.*\n/', '', $siteConf);

		if ($phpVersion) {
			$siteConf = '# Valet isolated PHP version : ' . $phpVersion . PHP_EOL . $siteConf;
		}

		return $siteConf;
	}

	/**
	 * Build the Nginx proxy config for the specified site.
	 *
	 * @param string $url The site to serve
	 * @param string $host The URL to proxy to, eg: http://127.0.0.1:8080
	 * @param bool $secure Is the proxy going to be secured? Default: `false`
	 * @return void
	 */
	public function proxyCreate($url, $host, $secure = false) {
		if (!preg_match('~^https?://.*$~', $host)) {
			throw new \InvalidArgumentException(sprintf('"%s" is not a valid URL', $host));
		}

		$tld = $this->config->read()['tld'];

		foreach (explode(',', $url) as $proxyUrl) {
			if (!str_ends_with($proxyUrl, '.' . $tld)) {
				$proxyUrl .= '.' . $tld;
			}

			$stub = $secure ? 'secure.proxy.valet.conf' : 'proxy.valet.conf';
			$siteConf = $this->files->getStub($stub);

			$siteConf = str_replace(
				['VALET_HOME_PATH', 'VALET_SERVER_PATH', 'VALET_STATIC_PREFIX', 'VALET_SITE', 'VALET_PROXY_HOST'],
				[$this->valetHomePath(), VALET_SERVER_PATH, VALET_STATIC_PREFIX, $proxyUrl, $host],
				$siteConf
			);

			if ($secure) {
				$this->secure($proxyUrl, $siteConf);
			}
			else {
				$this->unsecure($proxyUrl);

				$this->files->ensureDirExists($this->nginxPath(), user());
				$this->files->putAsUser($this->nginxPath($proxyUrl), $siteConf);
			}

			$protocol = $secure ? 'https' : 'http';

			info("Valet will now proxy [$protocol://" . $proxyUrl . "] traffic to [" . $host . "].");
		}
	}

	/**
	 * Unsecure the given URL so that it will use HTTP again.
	 *
	 * @param string $url
	 * @return void
	 */
	public function proxyDelete($url) {
		$tld = $this->config->read()['tld'];

		foreach (explode(',', $url) as $proxyUrl) {
			if (!str_ends_with($url, '.' . $tld)) {
				$protocol = $this->isSecured($proxyUrl) ? 'https' : 'http';

				$proxyUrl .= '.' . $tld;
			}

			$this->unsecure($proxyUrl);
			$this->files->unlink($this->nginxPath($proxyUrl));
			info("Valet will no longer proxy [$protocol://" . $proxyUrl . "].");
		}
	}

	/**
	 * Get all sites which are proxies (not Links, and contain proxy_pass directive).
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function proxies() {
		$dir = $this->nginxPath();
		$tld = $this->config->read()['tld'];
		$links = $this->links();
		$certs = $this->getCertificates();

		if (!$this->files->exists($dir)) {
			return collect();
		}

		$proxies = collect($this->files->scandir($dir))
			->filter(function ($site, $key) use ($tld) {
				// keep sites that match our TLD
				return str_ends_with($site, ".$tld.conf");
			})->map(function ($site, $key) use ($tld) {
				// remove the TLD suffix for consistency
				return str_replace(".$tld.conf", '', $site);
			})->reject(function ($site, $key) use ($links) {
				return $links->has($site);
			})->mapWithKeys(function ($site) {
				$host = $this->getProxyHostForSite($site) ?: '(other)';

				return [$site => $host];
			})->reject(function ($host, $site) {
				// If proxy host is null, it may be just a normal SSL/TLS stub, or something else; either way we exclude it from the list
				return $host === '(other)';
			})->map(function ($host, $site) use ($certs, $tld) {
				$secured = $certs->has($site);
				$url = ($secured ? 'https' : 'http') . '://' . $site . '.' . $tld;

				return [
					'site' => $site,
					'secured' => $secured ? 'X' : '',
					'url' => $url,
					'path' => $host
				];
			});

		return $proxies;
	}

	/**
	 * Identify whether a site is for a proxy by reading the host name from its config file.
	 *
	 * @param string $site Site name without TLD
	 * @param string $configContents Config file contents
	 * @return string|null
	 */
	public function getProxyHostForSite($site, $configContents = null) {
		$siteConf = $configContents ?: $this->getSiteConfigFileContents($site);

		if (empty($siteConf)) {
			return null;
		}

		$host = null;
		if (preg_match('~proxy_pass\s+(?<host>https?://.*)\s*;~', $siteConf, $patterns)) {
			$host = trim($patterns['host']);
		}

		return $host;
	}

	public function valetHomePath() {
		return Valet::homePath();
	}

	/**
	 * Get the path to Nginx site configuration files.
	 */
	public function nginxPath($additionalPath = null) {
		if ($additionalPath && !str_ends_with($additionalPath, '.conf')) {
			$additionalPath = $additionalPath . '.conf';
		}

		return $this->valetHomePath() . '/Nginx' . ($additionalPath ? '/' . $additionalPath : '');
	}

	/**
	 * Get the path to the linked Valet sites.
	 *
	 * @return string
	 */
	public function sitesPath($link = null) {
		return $this->valetHomePath() . '/Sites' . ($link ? '/' . $link : '');
	}

	/**
	 * Get the path to the Valet TLS certificates.
	 *
	 * @return string
	 */
	public function certificatesPath($url = null, $extension = null) {
		$url = $url ? '/' . $url : '';
		$extension = $extension ? '.' . $extension : '';

		return $this->valetHomePath() . '/Certificates' . $url . $extension;
	}

	/**
	 * Get the path to the Valet CA certificates.
	 *
	 * @return string
	 */
	public function caPath($caFile = null) {
		return $this->valetHomePath() . '/CA' . ($caFile ? '/' . $caFile : '');
	}
}
