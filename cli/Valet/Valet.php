<?php

namespace Valet;

use Httpful\Request;

class Valet
{
    public $cli;
    public $files;

    /**
     * Create a new Valet instance.
     *
     * @param CommandLine $cli
     * @param Filesystem  $files
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
    public function extensions()
    {
        if (! $this->files->isDir(VALET_HOME_PATH.'/Extensions')) {
            return [];
        }

        return collect($this->files->scandir(VALET_HOME_PATH.'/Extensions'))
                    ->reject(function ($file) {
                        return is_dir($file);
                    })
                    ->map(function ($file) {
                        return VALET_HOME_PATH.'/Extensions/'.$file;
                    })
                    ->values()->all();
    }

    /**
     * Determine if this is the latest version of Valet.
     *
     * @param string $currentVersion
     *
     * @return bool
     */
    public function onLatestVersion($currentVersion)
    {
        $response = Request::get('https://api.github.com/repos/cretueusebiu/valet-windows/releases/latest')->send();

        return version_compare($currentVersion, trim($response->body->tag_name, 'v'), '>=');
    }
}
