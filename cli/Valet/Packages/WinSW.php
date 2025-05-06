<?php

namespace Valet\Packages;

use function Valet\info_dump;

class WinSW extends GithubPackage {
	/**
	 * @var string The name of the package: `winsw`.
	 */
	protected $packageName = 'winsw';

	/**
	 * Array of download arguments to be passed to the download method.
	 *
	 * Each element of the array is an associative array that corresponds to
	 * a single downloadable file. The array consists of the following:
	 *
	 * @var array $downloadArgs `[$url, $filename, $filepath]`
	 *
	 * @var string $url Github API endpoint URL.
	 * @var string $filename The filename to find the file in the API response.
	 * @var string $filepath [Optional] The path to save the file. If not set, the `$filename` will be used.
	 */
	private $downloadArgs = [
		[
			"url" => "https://api.github.com/repos/winsw/winsw/releases/tags/v2.12.0",
			"filename" => "WinSW.NET4.exe",
			"filepath" => "winsw.exe"
		],
		[
			"url" => "https://api.github.com/repos/winsw/winsw/readme?ref=v2.12.0",
			"filename" => "README.md"
		]
	];

	/**
	 * Install WinSW from github releases.
	 */
	public function install() {
		if (!$this->isInstalled()) {
			$winswPath = $this->packagePath();

			$this->files->ensureDirExists($winswPath);

			foreach ($this->downloadArgs as $arg) {
				$this->download($arg["url"], $arg['filename'], $winswPath . '/' . ($arg['filepath'] ?? $arg['filename']));
			}

			$this->changeReadme();
		}
	}

	/**
	 * Change the readme file to use the correct URL for the WinSW package.
	 * This is a workaround for the fact that the readme file in the package
	 * contains relative links to the source code, which are not valid when
	 * the package is installed.
	 * The function replaces the relative links with absolute links to the
	 * source code on GitHub.
	 *
	 * The readme is mainly for dev purposes, to help understand how to use the package.
	 *
	 * @return void
	 */
	private function changeReadme() {
		$winswPath = $this->packagePath();

		// Get the contents of the readme file.
		$contents = $this->files->get("$winswPath/readme.md");

		/**
		 * Convert relative links to absolute URLs.
		 */

		// Split the contents into an array at the point of `](` to get the links.
		// This is because the readme file contains links in the format of `[link](url)`.
		$contents = preg_split("/\]\(/", $contents);


		foreach ($contents as $key => $item) {
			// If the item doesn't contain 'http', AND
			// if the item matches the regex pattern, then it's a relative link.
			// The regex pattern will match strings like `doc/yamlConfigFile.md`,
			// `LICENSE.txt`, etc.
			if (!str_contains($item, 'http') && preg_match("/[[:alpha:]]*(\/*[[:alpha:]])*\..+\)/", $item)) {
				// Prepend the github repo URL to the relative link and set back into the array.
				$contents[$key] = "https://github.com/winsw/winsw/blob/v2.12.0/$item";
			}
		}

		// Join the array back into a string with the `](` separator.
		$contents = implode("](", $contents);

		// Add the version number to the readme file.
		$contents = str_replace("Windows Service Wrapper", "Windows Service Wrapper (v2.12.0)", $contents);

		// Change the file with the new contents.
		$this->files->put("$winswPath/readme.md", $contents);
	}
}