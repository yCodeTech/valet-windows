<?php

namespace Valet;

class WinSW {
	/**
	 * @var string
	 */
	protected $service;

	/**
	 * @var string
	 */
	protected $serviceId;

	/**
	 * @var CommandLine
	 */
	protected $cli;

	/**
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * Create a new WinSW instance.
	 *
	 * @param  CommandLine  $cli
	 * @param  Filesystem  $files
	 * @return void
	 */
	public function __construct(string $service, string $serviceId, CommandLine $cli, Filesystem $files) {
		$this->cli = $cli;
		$this->files = $files;
		$this->service = $service;
		$this->serviceId = $serviceId;
	}

	/**
	 * Install the service.
	 *
	 * @param  array  $args
	 * @return void
	 */
	public function install(array $args = []) {
		$this->createConfiguration($args);

		$command = 'cmd "/C cd ' . $this->servicesPath() . ' && "' . $this->servicesPath($this->service) . '" install"';

		$this->cli->runOrExit($command, function ($code, $output) {
			error("Failed to install service [$this->service]. Check ~/.config/valet/Log for errors.\n$output");
		});
	}

	/**
	 * Create the .exe and .xml files.
	 *
	 * @param  array  $args
	 * @return void
	 */
	protected function createConfiguration(array $args = []) {
		$args['VALET_HOME_PATH'] = Valet::homePath();

		$this->files->copy(
			realpath(valetBinPath() . 'winsw/WinSW.NET4.exe'),
			$this->binaryPath()
		);

		$config = $this->files->get(__DIR__ . "/../stubs/$this->service.xml");

		$this->files->put(
			$this->configPath(),
			str_replace(array_keys($args), array_values($args), $config ?: '')
		);
	}

	/**
	 * Uninstall the service.
	 *
	 * @return void
	 */
	public function uninstall() {
		if ($this->isRunning()) {
			$this->stop();
		}

		$this->cli->run('cmd "/C cd ' . $this->servicesPath() . ' && "' . $this->servicesPath($this->service) . '" uninstall"');

		sleep(1);

		$this->files->unlink($this->binaryPath());
		$this->files->unlink($this->configPath());
	}

	/**
	 * Determine if the service is installed.
	 *
	 * @return bool
	 */
	public function installed(): bool {
		return $this->cli->powershell("Get-Service -Name \"$this->serviceId\"")->isSuccessful();
	}

	/**
	 * Restart the service.
	 *
	 * @return void
	 */
	public function restart() {
		$command = 'cmd "/C cd ' . $this->servicesPath() . ' && "' . $this->servicesPath($this->service) . '" restart"';

		$this->cli->run($command, function () use ($command) {
			sleep(2);

			$this->cli->runOrExit($command, function ($code, $output) {
				error("Failed to restart service [$this->service]. Check ~/.config/valet/Log for errors.\n$output");
			});
		});
	}

	/**
	 * Stop the service.
	 *
	 * @return void
	 */
	public function stop() {
		$command = 'cmd "/C cd ' . $this->servicesPath() . ' && "' . $this->servicesPath($this->service) . '" stop"';

		$this->cli->run($command, function ($code, $output) {
			warning("Failed to stop service [$this->service].\n$output");
		});
	}

	/**
	 * Is a service running?
	 *
	 * @return boolean
	 */
	public function isRunning() {
		$output = $this->cli->powershell('Get-Service -Name ' . $this->serviceId)->__toString();

		if (str_contains($output, "Running")) {
			return true;
		}
		return false;
	}

	/**
	 * Get the config path.
	 *
	 * @return string
	 */
	protected function configPath(): string {
		return $this->servicesPath("$this->service.xml");
	}

	/**
	 * Get the binary path.
	 *
	 * @return string
	 */
	protected function binaryPath(): string {
		return $this->servicesPath("$this->service.exe");
	}

	/**
	 * Get the services path.
	 *
	 * @param  string  $path
	 * @return string
	 */
	protected function servicesPath(string $path = ''): string {
		return Valet::homePath('Services' . ($path ? "/$path" : $path));
	}
}
