<?php

namespace Valet\ShareTools;

use Valet\CommandLine;
use Valet\Filesystem;
use Exception;
use DomainException;
use GuzzleHttp\Client;
use function Valet\retry;
use function Valet\info;

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
	 * Create a new instance.
	 *
	 * @param CommandLine $cli
	 * @param Filesystem $files
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
	 */
	abstract public function start(string $site, int $port, array $options = []);

	/**
	 * Run CLI commands
	 *
	 * @param string $command
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
	 *
	 * @param string $site The site
	 *
	 * @return string The current tunnel URL
	 */
	public function currentTunnelUrl(string $site) {
		info("Trying to retrieve tunnel URL...");

		foreach ($this->tunnelsEndpoints as $endpoint) {
			try {
				$response = retry(5, function () use ($endpoint, $site) {
					// Create a GuzzleHttp get request to the ngrok tunnels API and
					// get the body of the API response and decode the json.
					$body = json_decode((new Client())->get($endpoint)->getBody());

					// If tunnels is set in the body AND has more than 0 tunnels...
					if (isset($body->tunnels) && count($body->tunnels) > 0) {
						// If the tunnel URL is NOT null, return the URL.
						if ($tunnelUrl = $this->findHttpTunnelUrl($body->tunnels, $site)) {
							// Use | clip to copy the URL to the clipboard.
							$this->cli->passthru("echo $tunnelUrl | clip");

							return $tunnelUrl;
						}
					}

					throw new DomainException('Failed to retrieve tunnel URL.');
				}, 250);

				// If response is NOT empty, return the response.
				if (! empty($response)) {
					return $response;
				}
			}
			catch (Exception $e) {
				// Do nothing, suppress the exception to check the other port
			}
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
			if (($tunnel->proto === 'http' || $tunnel->proto === 'https') && strpos($tunnel->config->addr, $site)) {
				return $tunnel->public_url;
			}
		}
		return null;
	}
}
