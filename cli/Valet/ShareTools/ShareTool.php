<?php

namespace Valet\ShareTools;

use Valet\CommandLine;
use Valet\Filesystem;
use Exception;
use DomainException;
use GuzzleHttp\Client;
use function Valet\retry;

abstract class ShareTool {
	/**
	 * @var CommandLine
	 */
	protected $cli;

	/**
	 * @var Filesystem
	 */
	protected $files;

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
	 * @param Filesystem $files
	 * @return void
	 */
	public function __construct(CommandLine $cli, Filesystem $files) {
		$this->cli = $cli;
		$this->files = $files;
	}

	/**
	 * Start sharing.
	 *
	 * @param string $site The site
	 * @param int $port The site's port
	 * @param array $options Options/flags to pass to ngrok
	 *
	 * @return void
	 */
	abstract public function start(string $site, int $port, array $options = []);

	/**
	 * Run CLI commands
	 *
	 * @param string $command
	 *
	 * @return void
	 */
	abstract public function run(string $command);

	/**
	 * Get the config path.
	 *
	 * @return string The path.
	 */
	abstract public function getConfig();


	/*--------------------------------------------------------*
	 * Fully declared methods that don't need to be abstracts *
	 *--------------------------------------------------------*/


	/**
	 * Get the current tunnel URL from the API.
	 * @param string $site The site
	 *
	 * @return string The current tunnel URL
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
	 *
	 * @return string|null
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
}