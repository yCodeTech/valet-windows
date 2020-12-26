<?php

namespace Valet;

use DomainException;
use Httpful\Request;
use ZipArchive;

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
     * @param  string  $domain
     * @param  int $port
     * @return void
     */
    public function start(string $domain, int $port)
    {
        $ngrok = realpath(__DIR__.'/../../bin/ngrok.exe');

        $this->cli->passthru("start \"$domain\" \"$ngrok\" http $domain:$port -host-header=rewrite");
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

            if (! empty($response)) {
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
     * Ensure that Ngrok is installed.
     *
     * @return void
     */
    public function ensureInstalled()
    {
        $binPath = __DIR__.'/../../bin/ngrok.exe';
        $zipPath = __DIR__.'/../../bin/ngrok.zip';

        if (file_exists($binPath)) {
            return;
        }

        info('First time running Ngrok, downloading binary...');

        file_put_contents($zipPath, fopen('https://bin.equinox.io/c/4VmDzA7iaHb/ngrok-stable-windows-amd64.zip', 'r'));

        $zip = new ZipArchive;
        $zip->open($zipPath);
        $zip->extractTo(__DIR__.'/../../bin');
        $zip->close();
    }
}
