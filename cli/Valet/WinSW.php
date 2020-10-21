<?php

namespace Valet;

class WinSW
{
    protected $cli;
    protected $files;

    /**
     * Create a new WinSW instance.
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
     * Install a Windows service.
     *
     * @param string $service
     * @param array  $args
     *
     * @return void
     */
    public function install($service, $args = [])
    {
        $this->createConfiguration($service, $args);

        $bin = realpath(__DIR__.'/../../bin');
        $this->files->copy("$bin/winsw.exe", VALET_HOME_PATH."/Services/$service.exe");

        $command = 'cmd "/C cd '.VALET_HOME_PATH.'\Services && '.$service.' install"';
        $this->cli->runOrDie($command, function () use ($service) {
            warning("Could not install the $service service. Check ~/.valet/Log for errors.");
        });
    }

    /**
     * Create the service XML configuration file.
     *
     * @param string $service
     * @param array  $args
     *
     * @return void
     */
    protected function createConfiguration($service, $args = [])
    {
        $args['VALET_HOME_PATH'] = VALET_HOME_PATH;

        $contents = $this->files->get(__DIR__."/../stubs/$service.xml");

        $this->files->putAsUser(
            VALET_HOME_PATH."/Services/$service.xml",
            str_replace(array_keys($args), array_values($args), $contents)
        );
    }

    /**
     * Uninstall a Windows service.
     *
     * @param string $service
     *
     * @return void
     */
    public function uninstall($service)
    {
        $this->stop($service);

        $this->cli->run('cmd "/C cd '.VALET_HOME_PATH.'\Services && '.$service.' uninstall"');

        // $this->files->unlink(VALET_HOME_PATH."/Services/$service.exe");
        // $this->files->unlink(VALET_HOME_PATH."/Services/$service.xml");
    }

    /**
     * Restart a Windows service.
     *
     * @param string $service
     *
     * @return void
     */
    public function restart($service)
    {
        $this->stop($service);

        $command = 'cmd "/C cd '.VALET_HOME_PATH.'\Services && '.$service.' start"';

        $this->cli->run($command, function () use ($service, $command) {
            sleep(2);

            $this->cli->runOrDie($command, function () use ($service) {
                warning("Could not start the $service service. Check ~/.valet/Log for errors.");
            });
        });
    }

    /**
     * Stop a Windows service.
     *
     * @param string $service
     *
     * @return void
     */
    public function stop($service)
    {
        $this->cli->run('cmd "/C cd '.VALET_HOME_PATH.'\Services && '.$service.' stop"');
    }
}
