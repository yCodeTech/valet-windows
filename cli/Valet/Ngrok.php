<?php

namespace Valet;

use DomainException;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Symfony\Component\Yaml\Yaml;

class Ngrok {
	/**
	 * @var CommandLine
	 */
	protected $cli;

	/**
	 * @var array
	 */
	protected $tunnelsEndpoints = [
		'http://127.0.0.1:4040/api/tunnels',
		'http://127.0.0.1:4041/api/tunnels'
	];

	/**
	 * Create a new Nginx instance.
	 *
	 * @param CommandLine $cli
	 * @return void
	 */
	public function __construct(CommandLine $cli) {
		$this->cli = $cli;
	}

	/**
	 * @param string $command
	 * @return void
	 */
	public function run(string $command) {
		$ngrok = realpath(valetBinPath() . 'ngrok.exe');

		if (trim($command) === "update") {
			$command = "$command " . $this->getNgrokConfig();
		}
		if (trim($command) === "config upgrade") {
			$command = "$command " . $this->getNgrokConfig();
		}

		$this->cli->passthru("\"$ngrok\" $command");
	}

	/**
	 * @param string $site The site
	 * @param int $port The site's port
	 * @param array $options Options/flags to pass to ngrok
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

		$ngrokCommand = "\"$ngrok\" http $site:$port " . $this->getNgrokConfig() . " $options";

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
	public function getNgrokConfig(bool $asCliFlag = true) {
		$configPath = Valet::homePath() . "/Ngrok/ngrok.yml";
		if ($asCliFlag) {
			return "--config $configPath";
		}
		return $configPath;
	}

	/**
	 * Get the current tunnel URL from the ngrok API.
	 * @param string $site The site
	 *
	 * @return string $url The current tunnel URL
	 */
	public function currentTunnelUrl(string $site) {
		// Set a new GuzzleHttp client.
		$client = new Client();

		// Create a GuzzleHttp get request to the ngrok tunnels API.
		$response = $client->get('http://127.0.0.1:4040/api/tunnels');

		// Get the contents of the API response.
		$response = $response->getBody()->getContents();
		// Decode the json response into a properly formed PHP array.
		$tunnels = json_decode($response, true);

		// Find and get the public URL of the site.
		$url = $this->findHttpTunnelUrl($tunnels["tunnels"], $site);

		if (!empty($url)) {
			// Use | clip to copy the URL to the clipboard.
			$this->cli->passthru("echo $url | clip");
			return $url;
		}

		throw new DomainException('Tunnel not established.');
	}

	/**
	 * Find the HTTP tunnel URL from the list of tunnels.
	 *
	 * @param array $tunnels
	 * @return string|null
	 * @return void|string
	 */
	public function findHttpTunnelUrl(array $tunnels, ?string $site = null) {
		// If there are active tunnels on the ngrok instance we will spin through them and
		// find the one responding on HTTP. Each tunnel has an HTTP and a HTTPS address
		// but for local dev purposes we just desire the plain HTTP URL endpoint.
		foreach ($tunnels as $tunnel) {
			if (($tunnel["proto"] === 'http' || $tunnel["proto"] === 'https') && strpos($tunnel["config"]["addr"], $site)) {
				return $tunnel["public_url"];
			}
		}
	}

	/**
	 * Check if ngrok config exists and the authtoken is set.
	 *
	 * @return bool
	 */
	protected function hasAuthToken(): bool {
		// If the config file exists...
		if (file_exists($this->getNgrokConfig(false))) {
			// Read and parse the config yml file and convert to an associative array.
			$config = Yaml::parseFile($this->getNgrokConfig(false));

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
