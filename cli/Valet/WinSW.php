<?php

namespace Valet;

class WinSW
{
    /**
     * @var string
     */
    protected $service;

    /**
     * @var CommandLine
     */
    protected $cli;

    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * Create a new WinSW instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @return void
     */
    public function __construct(string $service, CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
        $this->service = $service;
    }

    /**
     * Install the service.
     *
     * @param  array  $args
     * @return void
     */
    public function install(array $args = [])
    {
        $this->createConfiguration($args);

        $command = 'cmd "/C cd '.$this->servicesPath().' && "'.$this->servicesPath($this->service).'" install"';

        $this->cli->runOrExit($command, function ($code, $output) {
            error("Failed to install service [$this->service]. Check ~/.config/valet/Log for errors.\n$output");
        });
    }

    /**
     * Create the .exe and .xml files.
     *
     * @param  array  $args
     * @return void
     */
    protected function createConfiguration(array $args = [])
    {
        $args['VALET_HOME_PATH'] = Valet::homePath();

        $this->files->copy(
            realpath(__DIR__.'/../../bin/winsw/WinSW.NET4.exe'),
            $this->binaryPath()
        );

        $config = $this->files->get(__DIR__."/../stubs/$this->service.xml");

        $this->files->put(
            $this->configPath(),
            str_replace(array_keys($args), array_values($args), $config ?: '')
        );
    }

    /**
     * Uninstall the service.
     *
     * @return void
     */
    public function uninstall()
    {
        $this->stop($this->service);

        $this->cli->run('cmd "/C cd '.$this->servicesPath().' && "'.$this->servicesPath($this->service).'" uninstall"');

        sleep(1);

        $this->files->unlink($this->binaryPath());
        $this->files->unlink($this->configPath());
    }

    /**
     * Determine if the service is installed.
     *
     * @return bool
     */
    public function installed(): bool
    {
        $name = 'valet_'.str_replace('service', '', $this->service);

        if ($name === 'valet_phpcgixdebug') {
            $name = 'valet_phpcgi_xdebug';
        }

        return $this->cli->powershell("Get-Service -Name \"$name\"")->isSuccessful();
    }

    /**
     * Restart the service.
     *
     * @return void
     */
    public function restart()
    {
        $command = 'cmd "/C cd '.$this->servicesPath().' && "'.$this->servicesPath($this->service).'" restart"';

        $this->cli->run($command, function () use ($command) {
            sleep(2);

            $this->cli->runOrExit($command, function ($code, $output) {
                error("Failed to restart service [$this->service]. Check ~/.config/valet/Log for errors.\n$output");
            });
        });
    }

    /**
     * Stop the service.
     *
     * @return void
     */
    public function stop()
    {
        $command = 'cmd "/C cd '.$this->servicesPath().' && "'.$this->servicesPath($this->service).'" stop"';

        $this->cli->run($command, function ($code, $output) {
            warning("Failed to stop service [$this->service].\n$output");
        });
    }

    /**
     * Get the config path.
     *
     * @return string
     */
    protected function configPath(): string
    {
        return $this->servicesPath("$this->service.xml");
    }

    /**
     * Get the binary path.
     *
     * @return string
     */
    protected function binaryPath(): string
    {
        return $this->servicesPath("$this->service.exe");
    }

    /**
     * Get the services path.
     *
     * @param  string  $path
     * @return string
     */
    protected function servicesPath(string $path = ''): string
    {
        return Valet::homePath('Services'.($path ? DIRECTORY_SEPARATOR.$path : $path));
    }
}
