<?php

namespace Valet;

use Httpful\Request;

class Valet
{
    protected $cli;
    protected $files;

    /**
     * Create a new Valet instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem   $files
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
    public function extensions()
    {
        $path = static::homePath('Extensions');

        if (! $this->files->isDir($path)) {
            return [];
        }

        return collect($this->files->scandir($path))
            ->reject(function ($file) {
                return is_dir($file);
            })
            ->map(function ($file) use ($path) {
                return $path.DIRECTORY_SEPARATOR.$file;
            })
            ->values()->all();
    }

    /**
     * Determine if this is the latest version of Valet.
     *
     * @param  string  $currentVersion
     * @return bool
     * @throws \Httpful\Exception\ConnectionErrorException
     */
    public function onLatestVersion($currentVersion)
    {
        $response = Request::get('https://api.github.com/repos/cretueusebiu/valet-windows/releases/latest')->send();

        return version_compare($currentVersion, trim($response->body->tag_name, 'v'), '>=');
    }

    /**
     * Run composer global diagnose
     */
    public function composerGlobalDiagnose()
    {
        $this->cli->runAsUser('composer global diagnose');
    }

    /**
     * Run composer global update
     */
    public function composerGlobalUpdate()
    {
        $this->cli->runAsUser('composer global update');
    }

    /**
     * Get the Valet home path (VALET_HOME_PATH = ~/.config/valet).
     *
     * @param  string $path
     * @return string
     */
    public static function homePath(string $path = ''): string
    {
        return VALET_HOME_PATH.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}
