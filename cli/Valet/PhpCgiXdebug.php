<?php

namespace Valet;

class PhpCgiXdebug extends PhpCgi
{
	const PORT = 9100;

	/**
	 * @inheritDoc
	 */
	public function __construct(CommandLine $cli, Filesystem $files, WinSwFactory $winswFactory, Configuration $configuration)
	{
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
	 * @return void
	 */
	public function install($phpVersion = null)
	{
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

		foreach ($phps as $php) {
			$this->installService($php['version']);
		}
	}

	/**
	 * Install the Windows service.
	 *
	 * @return void
	 */
	public function installService($phpVersion, $phpCgiServiceConfig = null, $installConfig = null)
	{
		$phpWinSw = $this->phpWinSws[$phpVersion];

		$phpCgiServiceConfig = $phpCgiServiceConfig ?? file_get_contents(__DIR__ . '/../stubs/phpcgixdebugservice.xml');
		$installConfig = $installConfig ?? [
			'PHP_PATH' => $phpWinSw['php']['path'],
			'PHP_XDEBUG_PORT' => $phpWinSw['php']['xdebug_port'],
		];

		parent::installService($phpVersion, $phpCgiServiceConfig, $installConfig);
	}

	/**
	 * Determine if the Xdebug is installed.
	 *
	 * @param string $phpVersion
	 * @return bool
	 */
	public function installed($phpVersion = null)
	{
		if (empty($phpVersion)) {
			$phps = $this->configuration->get('php', []);

			foreach ($phps as $php) {
				if ($this->isInstalledService($php['version'])) {
					return true;
				}
			}
			return;
		}

		if ($this->configuration->isPhpAlias($phpVersion)) {
			$phpVersion = $this->configuration->getPhpFullVersionByAlias($phpVersion);
		}

		return $this->isInstalledService($phpVersion);
	}

	/**
	 *
	 */
	private function isInstalledService($phpVersion)
	{
		$name = $this->getPhpCgiName($phpVersion);
		return $this->cli->powershell('Get-Service -Name "' . $name . '"')->isSuccessful();
	}

	/**
	 * Get the CGI name
	 *
	 * @param string $phpVersion
	 * @return string
	 */
	public function getPhpCgiName($phpVersion)
	{
		return $this->phpWinSws[$phpVersion]["phpCgiName"];
	}
}
