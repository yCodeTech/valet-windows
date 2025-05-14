<?php

namespace Valet;

class PhpCgiXdebug extends PhpCgi {
	const PORT = 9100;

	/**
	 * @inheritDoc
	 */
	public function __construct(CommandLine $cli, Filesystem $files, WinSwFactory $winswFactory, Configuration $configuration) {
		parent::__construct($cli, $files, $winswFactory, $configuration);

		foreach ($this->phpWinSws as $phpVersion => $phpWinSw) {
			$phpServiceName = "php{$phpVersion}cgi_xdebugservice";
			$serviceId = "valet_php{$phpVersion}cgi_xdebug-{$phpWinSw['php']['xdebug_port']}";

			$this->phpWinSws[$phpVersion]['phpServiceName'] = $phpServiceName;
			$this->phpWinSws[$phpVersion]['phpCgiName'] = $serviceId;
			$this->phpWinSws[$phpVersion]['winsw'] = $this->winswFactory->make($phpServiceName, $serviceId);
		}
	}

	/**
	 * Install and configure PHP CGI service.
	 *
	 * @param null|string $phpVersion The PHP version
	 * @return array|void $versionArray
	 */
	public function install($phpVersion = null) {
		if ($phpVersion) {
			if ($this->configuration->isPhpAlias($phpVersion)) {
				$phpVersion = $this->configuration->getPhpFullVersionByAlias($phpVersion);
			}

			if (!isset($this->phpWinSws[$phpVersion])) {
				error("PHP xDebug service for version {$phpVersion} not found", true);
			}

			$this->installService($phpVersion);

			return;
		}

		$phps = $this->configuration->get('php', []);

		// Set an empty array to push the version numbers into
		// to be able to return it to the command.
		$versionArray = [];

		foreach ($phps as $php) {
			$this->installService($php['version']);
			array_push($versionArray, $php['version']);
		}
		return $versionArray;
	}

	/**
	 * Install the Windows service.
	 *
	 * @return void
	 */
	public function installService($phpVersion, $phpCgiServiceConfig = null, $installConfig = null) {
		$phpWinSw = $this->phpWinSws[$phpVersion];

		$phpCgiServiceConfig ??= $this->files->getStub('phpcgixdebugservice.xml');

		$installConfig = $installConfig ?? [
			'PHP_PATH' => $phpWinSw['php']['path'],
			'PHP_XDEBUG_PORT' => $phpWinSw['php']['xdebug_port']
		];

		parent::installService($phpVersion, $phpCgiServiceConfig, $installConfig);
	}

	/**
	 * Determine if the Xdebug is installed.
	 *
	 * @param string|null $phpVersion
	 * @return bool
	 */
	public function installed($phpVersion = null) {
		if (empty($phpVersion)) {
			$phps = $this->configuration->get('php', []);

			// Loop through the PHP array until find any version
			// that Xdebug is installed for.
			// Ie. Installed returns true if ANY Xdebug is installed at the first occurrence
			foreach ($phps as $php) {
				if ($this->phpWinSws[$php["version"]]['winsw']->installed()) {
					return true;
				}
			}
			return false;
		}

		// Check if the PHP version supplied is the alias, then get the full version.
		if ($this->configuration->isPhpAlias($phpVersion)) {
			$phpVersion = $this->configuration->getPhpFullVersionByAlias($phpVersion);
		}

		return $this->phpWinSws[$phpVersion]['winsw']->installed();
	}
}
