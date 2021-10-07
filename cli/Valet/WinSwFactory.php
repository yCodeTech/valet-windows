<?php

namespace Valet;

class WinSwFactory
{
    /**
     * @var CommandLine
     */
    protected $cli;

    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * Create a new factory instance.
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
     * Make a new WinSW instance.
     *
     * @param  string  $service
     * @return WinSW
     */
    public function make(string $service)
    {
        return new WinSW(
            $service,
            $this->cli,
            $this->files
        );
    }
}
