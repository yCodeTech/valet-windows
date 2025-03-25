<?php

namespace Valet\ShareTools;

use Valet\Valet;
use Symfony\Component\Yaml\Yaml;

use function Valet\output;
use function Valet\info;
use function Valet\info_dump;
use function Valet\error;
use function Valet\valetBinPath;
use function Valet\prefixOptions;

class Ngrok extends ShareTool {
	/**
	 * Start sharing with ngrok.
	 *
	 * @param string $site The site
	 * @param int $port The site's port
	 * @param array $options Options/flags to pass to ngrok
	 *
	 * @return void
	 */
	public function start(string $site, int $port, array $options = []) {
		if ($port === 443 && !$this->hasAuthToken()) {
			output('Forwarding to local port 443 or a local https:// URL is only available after you sign up.
Sign up at: <fg=blue>https://ngrok.com/signup</>
Then use: <fg=magenta>valet set-ngrok-token [token]</>');
			exit(1);
		}

		// If host-header is not specified,
		// then set it into the array with a default value of rewrite.
		if (!stripos(json_encode($options), 'host-header')) {
			array_push($options, "host-header=rewrite");
		}

		$options = prefixOptions($options);

		$ngrok = realpath(valetBinPath() . 'ngrok.exe');

		$ngrokCommand = "\"$ngrok\" http $site:$port " . $this->getConfig() . " $options";

		info("Sharing $site...\n");
		info("To output the public URL, please open a new terminal and run `valet fetch-share-url $site`");

		$output = $this->cli->shellExec("$ngrokCommand 2>&1");

		if ($errors = strstr($output, "ERROR")) {
			error($errors . PHP_EOL);

			if (strpos($errors, 'ERR_NGROK_121') !== false) {
				info("To update ngrok yourself, please run `valet ngrok update` and then upgrade the config file by running `valet ngrok config upgrade`\n");
			}
		}
	}

	/**
	 * Run ngrok CLI commands.
	 *
	 * @param string $command
	 *
	 * @return void
	 */
	public function run(string $command) {
		$ngrok = realpath(valetBinPath() . 'ngrok.exe');

		// If command is `update` then append the config flag.
		if (trim($command) === "update") {
			$command = "$command " . $this->getConfig();
		}

		// If command is `config upgrade` then append the config flag.
		if (trim($command) === "config upgrade") {
			$command = "$command " . $this->getConfig();
		}

		$this->cli->passthru("\"$ngrok\" $command");
	}

	/**
	 * Get the ngrok configuration path
	 *
	 * @param bool $asCliFlag Determines whether to return the config path as a CLI --flag.
	 * Default `true`
	 *
	 * @return string Returns the ngrok config path as a CLI flag or just the path.
	 *
	 * `--config C:/Users/Username/.config/valet/Ngrok/ngrok.yml`
	 *
	 * OR
	 *
	 * `C:/Users/Username/.config/valet/Ngrok/ngrok.yml`
	 */
	public function getConfig(bool $asCliFlag = true) {
		$configPath = Valet::homePath() . "/Ngrok/ngrok.yml";
		if ($asCliFlag) {
			return "--config $configPath";
		}
		return $configPath;
	}

	/**
	 * Check if ngrok config exists and the authtoken is set.
	 *
	 * @return bool
	 */
	protected function hasAuthToken(): bool {
		// If the config file exists...
		if (file_exists($this->getConfig(false))) {
			// Read and parse the config yml file and convert to an associative array.
			$config = Yaml::parseFile($this->getConfig(false));

			// If config version is 2...
			if ($config["version"] === "2") {
				// Check the "authtoken" key exists in the array AND the value is NOT empty.
				// Then return the bool value.
				return (array_key_exists("authtoken", $config) && !empty($config["authtoken"]));
			}
			// If config version is 3...
			elseif ($config["version"] === "3") {
				// Check the "agent" key exists in the array AND the "authtoken" key exists in
				// the "agent" array AND the value is NOT empty.
				// Then return the bool value.
				return ((array_key_exists("agent", $config) && array_key_exists("authtoken", $config["agent"])) && !empty($config["agent"]["authtoken"]));
			}
		}
		return false;
	}
}
