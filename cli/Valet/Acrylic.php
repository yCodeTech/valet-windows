<?php

namespace Valet;

class Acrylic
{
    protected $cli;
    protected $files;

    /**
     * Create a new Acrylic instance.
     *
     * @param CommandLine $cli
     * @param Filesystem  $files
     *
     * @return void
     */
    public function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
    }

    /**
     * Install the Acrylic DNS service.
     *
     * @param string $tld
     *
     * @return void
     */
    public function install($tld = 'test')
    {
        $this->createHostsFile($tld);

        $this->configureNetworkDNS();

        $this->cli->runOrDie('cmd /C "'.$this->path().'/AcrylicUI.exe" InstallAcrylicService', function ($code, $output) {
            warning($output);
        });

        $this->restart();
    }

    /**
     * Create the AcrylicHosts file.
     *
     * @param string $tld
     *
     * @return void
     */
    public function createHostsFile($tld)
    {
        $contents = $this->files->get(__DIR__.'/../stubs/AcrylicHosts.txt');

        $this->files->put(
            $this->path().'/AcrylicHosts.txt',
            str_replace(['VALET_TLD', 'VALET_HOME_PATH'], [$tld, VALET_HOME_PATH], $contents)
        );

        $customConfigPath = VALET_HOME_PATH.'/AcrylicHosts.txt';

        if (! $this->files->exists($customConfigPath)) {
            $this->files->putAsUser($customConfigPath, PHP_EOL);
        }
    }

    /**
     * Configure the Network DNS.
     *
     * @return void
     */
    public function configureNetworkDNS()
    {
        $bin = realpath(__DIR__.'/../../bin');

        $this->cli->run('cmd /C cd "'.$bin.'" && configuredns');
    }

    /**
     * Update the tld used by Acrylic DNS.
     *
     * @param  string $tld
     * @return void
     */
    public function updateTld($tld)
    {
        $this->stop();

        $this->createHostsFile($tld);

        $this->restart();
    }

    /**
     * Uninstall the Acrylic DNS service.
     *
     * @return void
     */
    public function uninstall()
    {
        $this->stop();

        $this->cli->run('cmd /C "'.$this->path().'/AcrylicUI.exe" UninstallAcrylicService');
    }

    /**
     * Start the Acrylic DNS service.
     *
     * @return void
     */
    public function start()
    {
        $this->cli->runOrDie('cmd /C "'.$this->path().'/AcrylicUI.exe" StartAcrylicService', function ($code, $output) {
            warning($output);
        });

        $this->flushdns();
    }

    /**
     * Stop the Acrylic DNS service.
     *
     * @return void
     */
    public function stop()
    {
        $this->cli->run('cmd /C "'.$this->path().'/AcrylicUI.exe" StopAcrylicService');

        $this->flushdns();
    }

    /**
     * Restart the Acrylic DNS service.
     *
     * @return void
     */
    public function restart()
    {
        $this->stop();

        $this->start();
    }

    /**
     * Flush Windows DNS.
     *
     * @return void
     */
    public function flushdns()
    {
        $this->cli->run('cmd "/C ipconfig /flushdns"');
    }

    /**
     * Get the Acrylic path.
     *
     * @return string
     */
    public function path()
    {
        return str_replace(DIRECTORY_SEPARATOR, '/', realpath(__DIR__.'/../../bin/Acrylic/'));
    }
}
