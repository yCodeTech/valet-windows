<?php

namespace Valet;

class Acrylic {
	/**
	 * @var CommandLine
	 */
	protected $cli;

	/**
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * Create a new Acrylic instance.
	 *
	 * @param CommandLine $cli
	 * @param Filesystem $files
	 * @param Site $site
	 *
	 * @return void
	 */
	public function __construct(CommandLine $cli, Filesystem $files) {
		$this->cli = $cli;
		$this->files = $files;
	}

	/**
	 * Install Acrylic DNS.
	 *
	 * @param string $tld
	 */
	public function install(string $tld = 'test') {
		// Install the Acrylic package if it is not already installed.
		resolve(Packages\Acrylic::class)->install();
		// Install the Acrylic service and hosts file.
		$this->createHostsFile($tld);
		$this->installService();
	}

	/**
	 * Create the AcrylicHosts file.
	 *
	 * @param string $tld
	 */
	protected function createHostsFile(string $tld) {
		$contents = $this->files->getStub('AcrylicHosts.txt');

		$this->files->put(
			$this->path('AcrylicHosts.txt'),
			str_replace(['VALET_TLD', 'VALET_HOME_PATH'], [$tld, Valet::homePath()], $contents)
		);

		if (!$this->files->exists($configPath = Valet::homePath('AcrylicHosts.txt'))) {
			$this->files->putAsUser($configPath, PHP_EOL);
		}
	}

	/**
	 * Install the Acrylic DNS service.
	 */
	protected function installService() {
		$this->uninstall();

		$this->configureNetworkDNS();

		$this->cli->runOrExit(
			'cmd /C "' . $this->path('AcrylicUI.exe') . '" InstallAcrylicService',
			function ($code, $output) {
				error("Failed to install Acrylic DNS: $output");
			}
		);

		$this->flushdns();
	}

	/**
	 * Configure the Network DNS.
	 */
	protected function configureNetworkDNS() {
		$array = [
			'(Get-NetIPAddress -AddressFamily IPv4).InterfaceIndex | ForEach-Object {Set-DnsClientServerAddress -InterfaceIndex $_ -ServerAddresses (\"127.0.0.1\", \"8.8.8.8\")}',
			'(Get-NetIPAddress -AddressFamily IPv6).InterfaceIndex | ForEach-Object {Set-DnsClientServerAddress -InterfaceIndex $_ -ServerAddresses (\"::1\", \"2001:4860:4860::8888\")}'
		];

		$this->cli->powershell(implode(';', $array));
	}

	/**
	 * Update the tld used by Acrylic DNS.
	 *
	 * @param string $tld
	 */
	public function updateTld(string $tld) {
		$this->stop();

		$this->createHostsFile($tld);

		$this->restart();
	}

	/**
	 * Uninstall the Acrylic DNS service.
	 */
	public function uninstall() {
		if (!$this->installed()) {
			return;
		}

		$this->stop();

		$this->cli->run(
			'cmd /C "' . $this->path('AcrylicUI.exe') . '" UninstallAcrylicService',
			function ($code, $output) {
				warning("Failed to uninstall Acrylic DNS: $output");
			}
		);

		$this->removeNetworkDNS();

		$this->flushdns();
	}

	/**
	 * Determine if the Acrylic DNS is installed.
	 *
	 * @return bool
	 */
	protected function installed(): bool {
		return $this->cli->powershell('Get-Service -Name "AcrylicDNSProxySvc"')->isSuccessful();
	}

	/**
	 * Remove the Network DNS.
	 */
	protected function removeNetworkDNS() {
		$array = [
			'(Get-NetIPAddress -AddressFamily IPv4).InterfaceIndex | ForEach-Object {Set-DnsClientServerAddress -InterfaceIndex $_ -ResetServerAddresses}',
			'(Get-NetIPAddress -AddressFamily IPv6).InterfaceIndex | ForEach-Object {Set-DnsClientServerAddress -InterfaceIndex $_ -ResetServerAddresses}'
		];

		$this->cli->powershell(implode(';', $array));
	}

	/**
	 * Start the Acrylic DNS service.
	 */
	public function start() {
		$this->cli->runOrExit(
			'cmd /C "' . $this->path('AcrylicUI.exe') . '" StartAcrylicService',
			function ($code, $output) {
				error("Failed to start Acrylic DNS: $output");
			}
		);

		$this->flushdns();
	}

	/**
	 * Stop the Acrylic DNS service.
	 */
	public function stop() {
		$this->cli->run(
			'cmd /C "' . $this->path('AcrylicUI.exe') . '" StopAcrylicService',
			function ($code, $output) {
				warning("Failed to stop Acrylic DNS: $output");
			}
		);

		$this->flushdns();
	}

	/**
	 * Restart the Acrylic DNS service.
	 */
	public function restart() {
		$this->cli->run(
			'cmd /C "' . $this->path('AcrylicUI.exe') . '" RestartAcrylicService',
			function ($code, $output) {
				warning("Failed to restart Acrylic DNS: $output");
			}
		);

		$this->flushdns();
	}

	/**
	 * Flush Windows DNS.
	 */
	public function flushdns() {
		$this->cli->run('cmd "/C ipconfig /flushdns"');
	}

	/**
	 * Get the Acrylic path.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function path(string $path = ''): string {
		$basePath = str_replace("\\", '/', realpath(valetBinPath() . 'Acrylic'));

		return $basePath . ($path ? "/$path" : $path);
	}
}
