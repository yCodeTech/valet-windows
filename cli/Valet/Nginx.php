<?php

namespace Valet;

use DomainException;

class Nginx {
	/**
	 * @var CommandLine
	 */
	protected $cli;

	/**
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * @var Configuration
	 */
	protected $configuration;

	/**
	 * @var Site
	 */
	protected $site;

	/**
	 * @var WinSW
	 */
	protected $winsw;

	/**
	 * Create a new Nginx instance.
	 *
	 * @param CommandLine $cli
	 * @param Filesystem $files
	 * @param Configuration $configuration
	 * @param Site $site
	 * @param WinSwFactory $winsw
	 * @return void
	 */
	public function __construct(CommandLine $cli, Filesystem $files, Configuration $configuration, Site $site, WinSwFactory $winsw) {
		$this->cli = $cli;
		$this->site = $site;
		$this->files = $files;
		$this->winsw = $winsw->make('nginxservice', 'valet_nginx');
		$this->configuration = $configuration;
	}

	/**
	 * Install the configuration files for Nginx.
	 *
	 * @return void
	 */
	public function install() {
		$this->installConfiguration();
		$this->installServer();
		$this->installNginxDirectory();
		$this->installService();
	}

	/**
	 * Install the Nginx configuration file.
	 *
	 * @return void
	 */
	public function installConfiguration() {
		$defaultPhpVersion = $this->configuration->get('default_php');
		$defaultPhp = $this->configuration->getPhpByVersion($defaultPhpVersion);

		$this->files->putAsUser(
			$this->path('conf/nginx.conf'),
			str_replace(
				['VALET_USER', 'VALET_HOME_PATH', '__VALET_PHP_PORT__', '__VALET_PHP_XDEBUG_PORT__'],
				[user(), VALET_HOME_PATH, $defaultPhp['port'], $defaultPhp['xdebug_port']],
				$this->files->get(__DIR__ . '/../stubs/nginx.conf')
			)
		);
	}

	/**
	 * Install the Valet Nginx server configuration file.
	 *
	 * @return void
	 */
	public function installServer() {
		$defaultPhpVersion = $this->configuration->get('default_php');
		$defaultPhp = $this->configuration->getPhpByVersion($defaultPhpVersion);

		$this->files->ensureDirExists($this->path('valet'));

		$this->files->putAsUser(
			$this->path('valet/valet.conf'),
			str_replace(
				['VALET_HOME_PATH', 'VALET_SERVER_PATH', 'VALET_STATIC_PREFIX', 'HOME_PATH', 'VALET_PHP_PORT'],
				[VALET_HOME_PATH, VALET_SERVER_PATH, VALET_STATIC_PREFIX, $_SERVER['HOME'], $defaultPhp['port']],
				$this->files->get(__DIR__ . '/../stubs/valet.conf')
			)
		);

		$this->files->putAsUser(
			$this->path() . '/conf/fastcgi_params',
			$this->files->get(__DIR__ . '/../stubs/fastcgi_params')
		);
	}

	/**
	 * Install the Nginx configuration directory to the ~/.config/valet directory.
	 *
	 * This directory contains all site-specific Nginx servers.
	 *
	 * @return void
	 */
	public function installNginxDirectory() {
		if (!$this->files->isDir($nginxDirectory = Valet::homePath('Nginx'))) {
			$this->files->mkdirAsUser($nginxDirectory);
		}

		$this->files->putAsUser($nginxDirectory . '/.keep', "\n");

		$this->rewriteSecureNginxFiles();
	}

	/**
	 * Check nginx.conf for errors.
	 */
	private function lint() {
		$this->cli->run(
			'"' . $this->path('nginx.exe') . '" -c "' . $this->path('conf/nginx.conf') . '" -t -q -p "' . $this->path() . '"',
			function ($exitCode, $outputMessage) {
				$outputMessage = preg_replace("/\r\n|\n|\r/", "\r\n\r\n", $outputMessage);

				throw new DomainException("Nginx cannot start; please check your nginx.conf \r\n\r\nExit code $exitCode: \r\n\r\n$outputMessage");
			}
		);
	}

	/**
	 * Generate fresh Nginx servers for existing secure sites.
	 *
	 * @return void
	 */
	public function rewriteSecureNginxFiles() {
		$tld = $this->configuration->read()['tld'];

		$this->site->resecureForNewTld($tld, $tld);
	}

	/**
	 * Install the Windows service.
	 *
	 * @return void
	 */
	public function installService() {
		if ($this->winsw->installed()) {
			$this->uninstall();
		}

		$this->winsw->install([
			'NGINX_PATH' => $this->path()
		]);

		$this->winsw->restart();
	}

	/**
	 * Restart the Nginx service.
	 *
	 * @return void
	 */
	public function restart() {
		$this->killProcess();

		$this->lint();

		$this->winsw->restart();
	}

	/**
	 * Stop the Nginx service.
	 *
	 * @return void
	 */
	public function stop() {
		$this->killProcess();

		$this->winsw->stop();
	}

	/**
	 * Prepare Nginx for uninstallation.
	 *
	 * @return void
	 */
	public function uninstall() {
		$this->killProcess();

		$this->winsw->uninstall();
	}

	/**
	 * Kill all the nginx processes.
	 */
	public function killProcess() {
		$this->cli->run('cmd "/C taskkill /IM nginx.exe /F"');
	}

	/**
	 * Get the Nginx path.
	 *
	 * @param string $path
	 * @return string
	 */
	public function path(string $path = ''): string {
		return realpath(valetBinPath() . 'nginx') . ($path ? "/$path" : $path);
	}

	/**
	 * Check if the nginx service is installed.
	 *
	 * For use in valet.php to check if Valet is installed
	 * to enable most of the commands.
	 *
	 * @return boolean
	 */
	public function isInstalled() {
		return $this->winsw->installed();
	}
}