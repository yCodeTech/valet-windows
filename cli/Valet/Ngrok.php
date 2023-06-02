<?php

namespace Valet;

use DomainException;
use Httpful\Request;
use Illuminate\Support\Collection;

class Ngrok
{
	/**
	 * @var CommandLine
	 */
	protected $cli;

	/**
	 * @var array
	 */
	protected $tunnelsEndpoints = [
		'http://127.0.0.1:4040/api/tunnels',
		'http://127.0.0.1:4041/api/tunnels',
	];

	/**
	 * Create a new Nginx instance.
	 *
	 * @param  CommandLine  $cli
	 * @return void
	 */
	public function __construct(CommandLine $cli)
	{
		$this->cli = $cli;
	}

	/**
	 * @param  string  $command
	 * @return void
	 */
	public function run(string $command)
	{
		$ngrok = realpath(__DIR__ . '/../../bin/ngrok.exe');

		$this->cli->passthru("\"$ngrok\" $command");
	}

	/**
	 * @param  string  $domain
	 * @param  int  $port
	 * @param  array  $options
	 * @return void
	 */
	public function start(string $site, int $port, array $options = [])
	{
		if ($port === 443 && !$this->hasAuthToken()) {
			output('Forwarding to local port 443 or a local https:// URL is only available after you sign up.
Sign up at: <fg=blue>https://ngrok.com/signup</>
Then use: <fg=magenta>valet set-ngrok-token [token]</>');
			exit(1);
		}

		$options = (new Collection($options))->map(function ($value, $key) {
			return "--$key=$value";
		})->implode(' ');

		$ngrok = realpath(__DIR__ . '/../../bin/ngrok.exe');

		$newCMDtitle = "\"Sharing $site\"";
		$ngrokCommand = "\"$ngrok\" http $site:$port " . $this->getNgrokConfig() . " $options";

		$this->cli->passthru("start $newCMDtitle $ngrokCommand");
	}

	/**
	 * Get the ngrok configuration
	 * @return string Returns the ngrok config path as a CLI --flag:
	 * 
	 * `--config C:/Users/Username/ .config/valet/Ngrok/ngrok.yml`
	 */
	public function getNgrokConfig()
	{
		return "--config " . Valet::homePath() . "/Ngrok/ngrok.yml";
	}

	/**
	 * Get the current tunnel URL from the Ngrok API.
	 *
	 * @return string
	 */
	public function currentTunnelUrl(string $domain = null)
	{
		// wait a second for ngrok to start before attempting to find available tunnels
		// sleep(1);

		foreach ($this->tunnelsEndpoints as $endpoint) {
			$response = retry(20, function () use ($endpoint, $domain) {
				$body = Request::get($endpoint)->send()->body;

				if (isset($body->tunnels) && count($body->tunnels) > 0) {
					return $this->findHttpTunnelUrl($body->tunnels, $domain);
				}
			}, 250);

			if (!empty($response)) {
				return $response;
			}
		}

		throw new DomainException('Tunnel not established.');
	}

	/**
	 * Find the HTTP tunnel URL from the list of tunnels.
	 *
	 * @param  array  $tunnels
	 * @return string|null
	 * @return void
	 */
	public function findHttpTunnelUrl(array $tunnels, string $domain = null)
	{
		// If there are active tunnels on the Ngrok instance we will spin through them and
		// find the one responding on HTTP. Each tunnel has an HTTP and a HTTPS address
		// but for local dev purposes we just desire the plain HTTP URL endpoint.
		foreach ($tunnels as $tunnel) {
			if ($tunnel->proto === 'http' && strpos($tunnel->config->addr, $domain)) {
				return $tunnel->public_url;
			}
		}
	}

	/**
	 * @return bool
	 */
	protected function hasAuthToken(): bool
	{
		return file_exists(Valet::homePath() . '/Ngrok/ngrok.yml');
	}
}