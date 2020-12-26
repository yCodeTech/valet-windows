<?php

namespace Valet;

class WinSW
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
     * Create a new WinSW instance.
     *
     * @param  CommandLine $cli
     * @param  Filesystem  $files
     * @return void
     */
    public function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
    }

    /**
     * Install a Windows service.
     *
     * @param  string $service
     * @param  array  $args
     * @return void
     */
    public function install(string $service, array $args = [])
    {
        $this->createConfiguration($service, $args);

        $command = 'cmd "/C cd '.$this->servicesPath().' && '.$service.' install"';

        $this->cli->runOrExit($command, function ($code, $output) use ($service) {
            error("Failed to install service [$service]. Check ~/.config/valet/Log for errors.\n$output");
        });
    }

    /**
     * Create the .exe and .xml files.
     *
     * @param  string $service
     * @param  array  $args
     * @return void
     */
    protected function createConfiguration(string $service, array $args = [])
    {
        $args['VALET_HOME_PATH'] = Valet::homePath();

        $this->files->copy(
            realpath(__DIR__.'/../../bin/winsw/WinSW.NET4.exe'),
            $this->servicesPath("$service.exe")
        );

        $config = $this->files->get(__DIR__."/../stubs/$service.xml");

        $this->files->put(
            $this->servicesPath("$service.xml"),
            str_replace(array_keys($args), array_values($args), $config)
        );
    }

    /**
     * Uninstall a Windows service.
     *
     * @param  string $service
     * @return void
     */
    public function uninstall(string $service)
    {
        $this->stop($service);

        $this->cli->run('cmd "/C cd '.$this->servicesPath().' && '.$service.' uninstall"');

        $this->files->unlink($this->servicesPath("$service.exe"));
        $this->files->unlink($this->servicesPath("$service.xml"));
    }

    /**
     * Restart a Windows service.
     *
     * @param  string $service
     * @return void
     */
    public function restart(string $service)
    {
        $this->stop($service);

        $command = 'cmd "/C cd '.$this->servicesPath().' && '.$service.' start"';

        $this->cli->run($command, function () use ($service, $command) {
            sleep(2);

            $this->cli->runOrExit($command, function ($code, $output) use ($service) {
                error("Failed to start service [$service]. Check ~/.config/valet/Log for errors.\n$output");
            });
        });
    }

    /**
     * Stop a Windows service.
     *
     * @param  string $service
     * @return void
     */
    public function stop(string $service)
    {
        $command = 'cmd "/C cd '.$this->servicesPath().' && '.$service.' stop"';

        $this->cli->run($command, function ($code, $output) use ($service) {
            warning("Failed to stop service [$service].\n$output");
        });
    }

    /**
     * Get the services path.
     *
     * @param  string $path
     * @return string
     */
    protected function servicesPath(string $path = ''): string
    {
        return Valet::homePath('Services'.($path ? DIRECTORY_SEPARATOR.$path : $path));
    }
}
