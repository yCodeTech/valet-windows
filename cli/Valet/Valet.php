<?php

namespace Valet;

use GuzzleHttp\Client;
use Composer\CaBundle\CaBundle;

class Valet {
	protected $cli;
	protected $files;

	/**
	 * Create a new Valet instance.
	 *
	 * @param CommandLine $cli
	 * @param Filesystem $files
	 * @return void
	 */
	public function __construct(CommandLine $cli, Filesystem $files) {
		$this->cli = $cli;
		$this->files = $files;
	}

	/**
	 * Get the paths to all of the Valet extensions.
	 *
	 * @return array
	 */
	public function extensions(): array {
		$path = static::homePath('Extensions');

		if (!$this->files->isDir($path)) {
			return [];
		}

		return collect($this->files->scandir($path))->reject(function ($file) {
			return is_dir($file);
		})->map(function ($file) use ($path) {
			return "$path/$file";
		})->values()->all();
	}

	/**
	 * Get the installed Valet services.
	 *
	 * @param bool $disable Don't show the progressbar.
	 * Used in `install` command to query if the services are running.
	 *
	 * @return array
	 */
	public function services($disable = false): array {
		$phps = \Configuration::get('php', []);

		$phpCGIs = collect([]);
		$phpXdebugCGIs = collect([]);
		foreach ($phps as $php) {
			$phpCGIs->put("php {$php['version']}", \PhpCgi::getPhpCgiName($php['version']));
			$phpXdebugCGIs->put("php-xdebug {$php['version']}", \PhpCgiXdebug::getPhpCgiName($php['version']));
		}

		$services = collect([
			'acrylic' => 'AcrylicDNSProxySvc',
			'nginx' => 'valet_nginx'
		])->merge($phpCGIs)->merge($phpXdebugCGIs);

		// Set empty variable first, prevents errors when $disable is true.
		$progressBar = "";

		if (!$disable) {
			$progressBar = progressbar($services->count(), "Checking");
		}

		return $services->map(function ($id, $service) use ($progressBar, $disable) {
			$output = $this->cli->run('powershell -command "Get-Service -Name ' . $id . '"');

			if (!$disable) {
				$progressBar->setMessage(ucfirst($service), "placeholder");
				$progressBar->advance();
			}

			if (strpos($output, 'Running') > -1) {
				$status = '<fg=green>running</>';
			}
			elseif (strpos($output, 'Stopped') > -1) {
				$status = '<fg=yellow>stopped</>';
			}
			else {
				$status = '<fg=red>missing</>';
			}

			if (strpos($status, "missing") && strpos($service, "xdebug")) {
				$status = '<fg=red>not installed</>';
			}

			return [
				'service' => $service,
				'winname' => $id,
				'status' => $status
			];
		})->values()->all();
	}

	/**
	 * Determine if this is the latest version of Valet.
	 *
	 * @param string $currentVersion
	 * @return bool
	 *
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function onLatestVersion($currentVersion): bool {
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

		// Create a GuzzleHttp get request to the github API.
		$get = $client->request(
			"GET",
			'https://api.github.com/repos/ycodetech/valet-windows/releases/latest'
		);
		$response = json_decode($get->getBody()->getContents());

		return version_compare($currentVersion, trim($response->tag_name, 'v'), '>=');
	}

	/**
	 * Get a calculation of the percentage of parity completion against Laravel Valet for macOS
	 * @param string $url The URL to the raw code in Github of `app.php` of Laravel Valet on a released version.
	 * eg. https://raw.githubusercontent.com/laravel/valet/v4.3.0/cli/app.php
	 * @return void
	 */
	public function parity($url) {

		// Get the contents of the URL.
		$contents = $this->files->get($url);

		// Split the string by the strings "$app->" and "function" and collect them.
		$collection = collect(preg_split('/(\$app->|, function)+/', $contents));

		// Filter the collection to get only the commands in the form of command(command-name.
		$macOScommands = $collection->filter(function ($item, $key) {
			if (str_contains($item, "command(")) {
				return $item;
			}
			// Then rearrange the values into a new collection with their indexes reset.
			// Then split the items by the strings "'" and whitespace to get the command name only,
			// and remap them to a new collection and output as an array.
		})->values()->map(function ($item, $key) {
			$item = preg_split("/('|\s)/", $item);
			return $item[1];
		})->toArray();

		// Define commands in the macOS version that will not be available to make parity.
		$commandsNotApplicableForParity = [
			"composer",
			"loopback",
			"trust"
		];

		// Define Valet 3.0 commands that are new only to this Windows version.
		$newValetCommands = [
			"ngrok",
			"parity",
			"php:add",
			"php:install",
			"php:remove",
			"php:uninstall",
			"php:list",
			"sudo",
			"sites",
			"xdebug:install",
			"xdebug:uninstall"
		];

		// Run symfony's `valet list --raw` command and collect the un-styled raw output
		// of all commands.
		$valetCommands = collect(explode("\n", $this->cli->run("valet list --raw")->__toString()));

		/**
		 * Find the commands that have been made parity.
		 */

		// Remap and split at the point of multiple whitespace and return the first item.
		// This will strip out the descriptions of the commands, therefore only returning the
		// command name.
		$valetCommands = $valetCommands->map(function ($item, $key) {
			$item = preg_split("/\s\s\s/", $item);
			return $item[0];
			// Filter the collection and discard any commands that symfony adds automatically,
			// and also discard any new Valet 3.0 only commands.
		})->filter(function ($item, $key) use ($newValetCommands) {

			// Define symfony commands to discard.
			$discardElements = [
				"completion",
				"help",
				"list"
			];

			if (!in_array($item, $discardElements) && !in_array($item, $newValetCommands)) {
				return $item;
			}
		})->values()->all();

		// Total mac commands.
		$total_Commands = count($macOScommands);
		// Total commands that can be made parity.
		$total_CommandsForParity = $total_Commands - count($commandsNotApplicableForParity);
		// Total commands parity complete.
		$total_CommandsCompleted = count($valetCommands);
		// Total Valet 3.0 only commands.
		$total_NewValetCommands = count($newValetCommands);

		// Calculate the parity percentage.
		$parityPercentage = round($total_CommandsCompleted / $total_Commands * 100);
		// Calculate the total percentage it is possible to make parity.
		$total_PossibleParityPercentage = round($total_CommandsForParity / $total_Commands * 100);

		info("Out of a total $total_Commands commands, $total_CommandsForParity are possible for parity, with $total_CommandsCompleted complete, and $total_NewValetCommands brand new commands.");

		// Find the version from the URL.
		preg_match("/v[0-9]\.[0-9]\.[0-9]+/", $url, $macVersion);
		$macVersion = implode("", $macVersion);

		info("Parity at $parityPercentage% out of a total $total_PossibleParityPercentage% possible parity with the Laravel Valet (macOS) $macVersion");
	}

	/**
	 * Get the path to the home directory of composer global.
	 *
	 * While the default is "~/AppData/Roaming/Composer",
	 * composer does allow the user to change where global packages are installed.
	 * So we need to essentially ask composer where the home directory is.
	 *
	 * This is used by the `Diagnose` class.
	 *
	 * @return string The path to the global composer directory.
	 */
	public function getComposerGlobalPath() {
		return $this->cli->run('composer -n config --global home');
	}

	/**
	 * Get the Valet home path (VALET_HOME_PATH = ~/.config/valet).
	 *
	 * @param string $path
	 * @return string
	 */
	public static function homePath(string $path = ''): string {
		return VALET_HOME_PATH . ($path ? "/$path" : $path);
	}
}
