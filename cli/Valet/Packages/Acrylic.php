<?php

namespace Valet\Packages;

use function Valet\error;
use function Valet\info;
use function Valet\info_dump;
use function Valet\warning;

class Acrylic extends Package {
	/**
	 * @var string The name of the package: `acrylic`.
	 */
	protected $packageName = 'acrylic';

	protected $packageExeName = 'AcrylicUI';

	/**
	 * Install the Acrylic DNS proxy.
	 */
	public function install() {
		if (!$this->isInstalled()) {
			$acrylicPath = $this->packagePath();
			$zipFilePath = $this->packageZipFilePath();

			$this->files->ensureDirExists($acrylicPath);

			$this->download('https://sourceforge.net/projects/acrylic/best_release.json', 'Acrylic-Portable.zip', $zipFilePath);

			$this->unzip();

			$this->cleanUpPackageDirectory("", [".bat"]);

			$this->changeLocalIpv4BindingAddress();
		}
	}

	/**
	 * Download the Acrylic package from SourceForge.
	 */
	protected function download(string $url, string $filename, string $filePath) {
		// Find the latest release from SourceForge.
		// NOTE: SourceForge does not have a proper API for releases, so we use the best_release.json endpoint.
		$latestRelease = $this->getApiResponse($url);

		// The download URL for the latest .exe file.
		$downloadUrl = $latestRelease->release->url;

		// Replace the Acrylic.exe with the portable zip filename; ie. Acrylic-Portable.zip.
		// This is a workaround since the API doesn't provide a direct link to the
		// portable version.
		$downloadUrl = str_replace("Acrylic.exe", $filename, $downloadUrl);

		if (!isset($downloadUrl)) {
			error("The download URL was not found in the response. The API URL queried is: $url\n", true);
		}

		// Download the file via Guzzle.
		$this->downloadFile($downloadUrl, $filePath);
	}

	/**
	 * Change the local IPv4 binding address in the Acrylic configuration file to `127.0.0.1`.
	 */
	private function changeLocalIpv4BindingAddress() {
		// Get the original contents of the AcrylicConfiguration.ini file.
		$contents = $this->files->get($this->packagePath() . '/AcrylicConfiguration.ini');
		// Get the stub for the IPv4 binding address.
		$ipv4Address = $this->files->getStub('Acrylic_IPv4_Binding_Address.ini');

		// Replace the default Local IPv4 Binding Address in the config file with the stub.
		$this->files->put(
			$this->packagePath() . '/AcrylicConfiguration.ini',
			str_replace("LocalIPv4BindingAddress=0.0.0.0", $ipv4Address, $contents)
		);
	}
}
