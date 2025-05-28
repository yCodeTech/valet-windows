<?php

namespace Valet;

use Symfony\Component\Process\PhpExecutableFinder;

class PhpCgi {
	const PORT = 9001;

	/**
	 * @var CommandLine
	 */
	protected $cli;

	/**
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * @var WinSW
	 */
	protected $winsw;

	/**
	 * @var WinSwFactory
	 */
	protected $winswFactory;

	/**@var array
	 */
	protected $phpWinSws;

	/**
	 * @var Configuration
	 */
	protected $configuration;

	/**
	 * Create a new PHP CGI class instance.
	 *
	 * @param CommandLine $cli
	 * @param Filesystem $files
	 * @param WinSwFactory $winsw
	 * @param Configuration $configuration
	 * @return void
	 */
	public function __construct(CommandLine $cli, Filesystem $files, WinSwFactory $winswFactory, Configuration $configuration) {
		$this->cli = $cli;
		$this->files = $files;
		$this->winswFactory = $winswFactory;
		$this->configuration = $configuration;

		$phps = $this->configuration->get('php', []);

		foreach ($phps as $php) {
			$phpServiceName = "php{$php['version']}cgiservice";
			$serviceId = "valet_php{$php['version']}cgi-{$php['port']}";

			$this->phpWinSws[$php['version']] = [
				'phpServiceName' => $phpServiceName,
				'phpCgiName' => $serviceId,
				'php' => $php,
				'winsw' => $this->winswFactory->make($phpServiceName, $serviceId)
			];
		}
	}

	/**
	 * Install and configure PHP CGI service.
	 *
	 * @return void
	 */
	public function install($phpVersion = null) {
		if ($phpVersion) {
			if ($this->configuration->isPhpAlias($phpVersion)) {
				$phpVersion = $this->configuration->getPhpFullVersionByAlias($phpVersion);
			}

			if (!isset($this->phpWinSws[$phpVersion])) {
				error("PHP service for version {$phpVersion} not found", true);
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
	public function installService($phpVersion, $phpCgiServiceConfig = null, $installConfig = null) {
		$phpWinSw = $this->phpWinSws[$phpVersion];

		if ($phpWinSw['winsw']->installed()) {
			$phpWinSw['winsw']->uninstall();
		}

		// copy default phpcgiservice stub to create a new one
		$phpCgiServiceConfigArgs['PHPCGINAME'] = $phpWinSw['phpCgiName'];
		$phpCgiServiceConfig ??= $this->files->getStub('phpcgiservice.xml');

		$this->files->put(
			__DIR__ . "/../stubs/{$phpWinSw['phpServiceName']}.xml",
			str_replace(array_keys($phpCgiServiceConfigArgs), array_values($phpCgiServiceConfigArgs), $phpCgiServiceConfig ?: '')
		);

		$phpWinSw['winsw']->install($installConfig ?? [
			'PHP_PATH' => $phpWinSw['php']['path'],
			'PHP_PORT' => $phpWinSw['php']['port']
		]);

		$phpWinSw['winsw']->restart();
	}

	/**
	 * Uninstall the PHP CGI service.
	 *
	 * @return void
	 */
	public function uninstall($phpVersion = null) {
		if ($phpVersion) {
			if ($this->configuration->isPhpAlias($phpVersion)) {
				$phpVersion = $this->configuration->getPhpFullVersionByAlias($phpVersion);
			}

			if (!isset($this->phpWinSws[$phpVersion])) {
				error("PHP service for version [{$phpVersion}] not found", true);
			}
			$this->uninstallService($phpVersion);

			return;
		}

		$phps = $this->configuration->get('php', []);

		foreach ($phps as $php) {
			$this->uninstallService($php['version']);
		}
	}

	/**
	 * Install the Windows service.
	 *
	 * @return void
	 */
	public function uninstallService($phpVersion) {
		$phpWinSw = $this->phpWinSws[$phpVersion];

		if ($phpWinSw['winsw']->installed()) {
			$phpWinSw['winsw']->uninstall();
		}
	}

	/**
	 * Restart the PHP CGI service.
	 *
	 * @return void
	 */
	public function restart() {
		foreach ($this->phpWinSws as $phpWinSw) {
			if ($phpWinSw['winsw']->installed()) {
				$phpWinSw['winsw']->restart();
			}
		}
	}

	/**
	 * Stop the PHP CGI service.
	 *
	 * @return void
	 */
	public function stop() {
		foreach ($this->phpWinSws as $phpWinSw) {
			if ($phpWinSw['winsw']->installed()) {
				$phpWinSw['winsw']->stop();
			}
		}
	}

	/**
	 * Find the PHP path.
	 *
	 * @return string
	 */
	public function findDefaultPhpPath(): string {
		if (!$php = (new PhpExecutableFinder())->find()) {
			$php = $this->cli->runOrExit('where php', function () {
				error('Failed to find PHP. Make sure it\'s added to the path environment variables.');
			});
		}

		return pathinfo(explode("\n", $php)[0], PATHINFO_DIRNAME);
	}

	/**
	 * Find the PHP version from the path.
	 *
	 * @param string $phpPath The path to the PHP executable.
	 * @param bool $getExecPath Optional. Determines whether to return the executable path. Default: `false`.
	 * @return string|bool The PHP version or the executable path determined by the `$getExecPath` param, returns `false` on error.
	 */
	public function findPhpVersion($phpPath, $getExecPath = false) {
		$phpExecPath = "{$phpPath}/php.exe";

		if (!file_exists($phpExecPath)) {
			error("Failed to find the PHP executable in {$phpPath}");
			return false;
		}

		$phpVersion = $this->cli->runOrExit("\"$phpExecPath\" -r \"echo PHP_VERSION;\"",
			function ($code, $output) use ($phpPath) {
				error("Failed to get the PHP version for {$phpPath}");
			}
		);

		return $getExecPath ? $phpExecPath : $phpVersion->getOutput();
	}

	/**
	 * Get the PHP path by version.
	 * @param string $phpVersion
	 *
	 * @return string|null The path to the PHP executable.
	 */
	public function getPhpPath($phpVersion) {
		$phpPath = $this->phpWinSws[$phpVersion]["php"]["path"];
		$phpExecPath = $this->findPhpVersion($phpPath, true);

		if (!$phpExecPath) {
			error("PHP executable path not found for version {$phpVersion}");
			return false;
		}

		return $phpExecPath;
	}

	/**
	 * Get the CGI name
	 *
	 * @param string $phpVersion
	 * @return string
	 */
	public function getPhpCgiName($phpVersion) {
		return $this->phpWinSws[$phpVersion]["phpCgiName"];
	}
}