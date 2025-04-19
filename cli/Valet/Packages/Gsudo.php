<?php

namespace Valet\Packages;

use Valet\CommandLine;
use Valet\Filesystem;

use GuzzleHttp\Client;
use Composer\CaBundle\CaBundle;

use function Valet\info;
use function Valet\info_dump;
use function Valet\valetBinPath;

class Gsudo {
	/**
	 * @var CommandLine
	 */
	protected $cli;

	/**
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * @var Client
	 */
	protected $client;

	public function __construct(CommandLine $cli, Filesystem $files) {
		$this->cli = $cli;
		$this->files = $files;
		$this->client = new Client([
			\GuzzleHttp\RequestOptions::VERIFY => CaBundle::getSystemCaRootBundlePath()
		]);
	}

	/**
	 * Install gsudo
	 */
	public function install() {
		if (!$this->isInstalled()) {
			$zipFilePath = valetBinPath() . 'gsudo/gsudo.portable.zip';
			$gsudoPath = valetBinPath() . 'gsudo';

			$this->files->ensureDirExists($gsudoPath);

			$this->downloadGsudo($zipFilePath);
			$this->files->unzip($zipFilePath, $gsudoPath);

			// For each directory in the gsudo directory...
			foreach ($this->files->scandir($gsudoPath) as $dir) {
				// Move the necessary files to the gsudo directory.
				$this->moveX64Files($dir, $gsudoPath);

				// Remove all unnecessary directories and files.
				$this->files->unlink("$gsudoPath/$dir");
			}

			$this->configureGsudo();
		}
	}

	/**
	 * Check if gsudo is installed.
	 *
	 * @return bool
	 */
	public function isInstalled() {
		return $this->cli->run($this->gsudoPath() . " --version")->isSuccessful();
	}

	/**
	 * Get the path to the gsudo executable.
	 *
	 * @return string
	 */
	public function gsudoPath() {
		return valetBinPath() . 'gsudo/gsudo.exe';
	}

	private function downloadGsudo($zipFilePath) {
		$release = json_decode($this->client->get("https://api.github.com/repos/gerardog/gsudo/releases/latest")->getBody());

		foreach ($release->assets as $asset) {
			if ($asset->name === 'gsudo.portable.zip') {
				$downloadUrl = $asset->browser_download_url;
				break;
			}
		}

		// Download the zip file via Guzzle.
		$this->client->get($downloadUrl, [
			\GuzzleHttp\RequestOptions::SINK => $zipFilePath
		]);
	}

	/**
	 * Move x64 files to the main gsudo directory.
	 *
	 * @param string $dir
	 * @param string $gsudoPath
	 */
	private function moveX64Files($dir, $gsudoPath) {
		// If the directory is x64...
		if ($dir === 'x64') {
			// For each file...
			foreach ($this->files->scandir("$gsudoPath/$dir") as $file) {
				// Move the file to the main gsudo directory.
				$this->files->move("$gsudoPath/$dir/$file", "$gsudoPath/$file");
			}
		}
	}

	/**
	 * Configure Gsudo settings.
	 */
	private function configureGsudo() {
		$gsudo = '"' . $this->gsudoPath() . '"';
		$this->cli->passthru("$gsudo config CacheMode Auto");
	}

	/**
	 * Run as the Local System account (NT AUTHORITY\SYSTEM).
	 * @return string
	 */
	public function runAsSystem() {
		return '"' . $this->gsudoPath() . '" --system -d ';
	}
	/**
	 * Run as the Trusted Installer account (NT SERVICE\TrustedInstaller).
	 * @return string
	 */
	public function runAsTrustedInstaller() {
		return '"' . $this->gsudoPath() . '" --ti -d ';
	}
}