<?php

namespace Valet;

use GuzzleHttp\Client;
use Composer\CaBundle\CaBundle;

class Valet
{
	protected $cli;
	protected $files;

	/**
	 * Create a new Valet instance.
	 *
	 * @param  CommandLine  $cli
	 * @param  Filesystem  $files
	 * @return void
	 */
	public function __construct(CommandLine $cli, Filesystem $files)
	{
		$this->cli = $cli;
		$this->files = $files;
	}

	/**
	 * Get the paths to all of the Valet extensions.
	 *
	 * @return array
	 */
	public function extensions(): array
	{
		$path = static::homePath('Extensions');

		if (!$this->files->isDir($path)) {
			return [];
		}

		return collect($this->files->scandir($path))
			->reject(function ($file) {
				return is_dir($file);
			})
			->map(function ($file) use ($path) {
				return $path . DIRECTORY_SEPARATOR . $file;
			})
			->values()->all();
	}

	/**
	 * Get the installed Valet services.
	 *
	 * @return array
	 */
	public function services(): array
	{
		$phps = \Configuration::get('php', []);

		$phpCGIs = collect([]);
		$phpXdebugCGIs = collect([]);
		foreach ($phps as $php) {
			$phpCGIs->put("php {$php['version']}", \PhpCgi::getPhpCgiName($php['version']));
			$phpXdebugCGIs->put("php-xdebug {$php['version']}", \PhpCgiXdebug::getPhpCgiName($php['version']));
		}

		return collect([
			'acrylic' => 'AcrylicDNSProxySvc',
			'nginx' => 'valet_nginx',
		])->merge($phpCGIs)->merge($phpXdebugCGIs)
			->map(function ($id, $service) {
				$output = $this->cli->run('powershell -command "Get-Service -Name ' . $id . '"');

				if (strpos($output, 'Running') > -1) {
					$status = '<fg=green>running</>';
				} elseif (strpos($output, 'Stopped') > -1) {
					$status = '<fg=yellow>stopped</>';
				} else {
					$status = '<fg=red>missing</>';
				}

				return [
					'service' => $service,
					'winname' => $id,
					'status' => $status,
				];
			})->values()->all();
	}

	/**
	 * Determine if this is the latest version of Valet.
	 *
	 * @param  string  $currentVersion
	 * @return bool
	 *
	 * @throws \GuzzleHttp\Exception
	 */
	public function onLatestVersion($currentVersion): bool
	{
		/**
		 * Set a new GuzzleHttp client and use the Composer\CaBundle package
		 * to find and use the TLS CA bundle in order to verify the TLS/SSL
		 * certificate of the requesting website/API.
		 * Otherwise, Guzzle errors out with a curl error.
		 * 
		 * Code from StackOverflow answer: https://stackoverflow.com/a/53823135/2358222
		 */
		$client = new Client([
			\GuzzleHttp\RequestOptions::VERIFY => CaBundle::getSystemCaRootBundlePath()
		]);

		// Create a GuzzleHttp get request to the ngrok tunnels API.
		$get = $client->request(
			"GET",
			'https://api.github.com/repos/ycodetech/valet-windows/releases/latest'
		);
		$response = json_decode($get->getBody()->getContents());

		return version_compare($currentVersion, trim($response->tag_name, 'v'), '>=');
	}

	/**
	 * Run composer global diagnose.
	 */
	public function composerGlobalDiagnose()
	{
		$this->cli->runAsUser('composer global diagnose');
	}

	/**
	 * Run composer global update.
	 */
	public function composerGlobalUpdate()
	{
		$this->cli->runAsUser('composer global update');
	}

	/**
	 * Get the Valet home path (VALET_HOME_PATH = ~/.config/valet).
	 *
	 * @param  string  $path
	 * @return string
	 */
	public static function homePath(string $path = ''): string
	{
		return VALET_HOME_PATH . ($path ? DIRECTORY_SEPARATOR . $path : $path);
	}
}