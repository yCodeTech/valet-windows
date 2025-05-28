<?php

namespace Valet\Packages;

use Valet\CommandLine;
use Valet\Filesystem;

use GuzzleHttp\Client;
use Composer\CaBundle\CaBundle;

use function Valet\info;
use function Valet\info_dump;

class Gsudo extends GithubPackage {
	/**
	 * @var string The name of the package: `gsudo`.
	 */
	protected $packageName = 'gsudo';

	/**
	 * Install gsudo
	 */
	public function install() {
		if (!$this->isInstalled()) {
			$gsudoPath = $this->packagePath();
			$zipFilePath = "$gsudoPath/gsudo.portable.zip";

			$this->files->ensureDirExists($gsudoPath);

			$this->download('https://api.github.com/repos/gerardog/gsudo/releases/latest', 'gsudo.portable.zip', $zipFilePath);

			$this->files->unzip($zipFilePath, $gsudoPath);

			// Clean up the package directory.
			$this->cleanUpPackageDirectory($zipFilePath, "x64");

			$this->configureGsudo();
		}
	}

	/**
	 * Configure Gsudo settings.
	 */
	private function configureGsudo() {
		$gsudo = '"' . $this->packageExe() . '"';
		$this->cli->passthru("$gsudo config CacheMode Auto");
	}

	/**
	 * Run as the Local System account (NT AUTHORITY\SYSTEM).
	 * @return string
	 */
	public function runAsSystem() {
		return '"' . $this->packageExe() . '" --system -d ';
	}
	/**
	 * Run as the Trusted Installer account (NT SERVICE\TrustedInstaller).
	 * @return string
	 */
	public function runAsTrustedInstaller() {
		return '"' . $this->packageExe() . '" --ti -d ';
	}
}