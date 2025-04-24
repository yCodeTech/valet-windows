<?php

namespace Valet\Packages;

use Valet\CommandLine;
use Valet\Filesystem;

use GuzzleHttp\Client;
use Composer\CaBundle\CaBundle;

use function Valet\info;
use function Valet\info_dump;
use function Valet\valetBinPath;

class Gsudo extends GithubPackage {
	/**
	 * Install gsudo
	 */
	public function install() {
		if (!$this->isInstalled("gsudo")) {
			$zipFilePath = valetBinPath() . 'gsudo/gsudo.portable.zip';
			$gsudoPath = valetBinPath() . 'gsudo';

			$this->files->ensureDirExists($gsudoPath);

			$this->download('https://api.github.com/repos/gerardog/gsudo/releases/latest', 'gsudo.portable.zip', $zipFilePath);

			$this->files->unzip($zipFilePath, $gsudoPath);

			// Clean up the package directory.
			$this->cleanUpPackageDirectory($gsudoPath);

			$this->configureGsudo();
		}
	}

	/**
	 * Configure Gsudo settings.
	 */
	private function configureGsudo() {
		$gsudo = '"' . $this->packagePath("gsudo") . '"';
		$this->cli->passthru("$gsudo config CacheMode Auto");
	}

	/**
	 * Run as the Local System account (NT AUTHORITY\SYSTEM).
	 * @return string
	 */
	public function runAsSystem() {
		return '"' . $this->packagePath("gsudo") . '" --system -d ';
	}
	/**
	 * Run as the Trusted Installer account (NT SERVICE\TrustedInstaller).
	 * @return string
	 */
	public function runAsTrustedInstaller() {
		return '"' . $this->packagePath("gsudo") . '" --ti -d ';
	}
}