<?php

namespace Valet;

use Symfony\Component\Process\PhpExecutableFinder;

class PhpCgi
{
    const PORT = 9001;

    /**
     * @var CommandLine
     */
    protected $cli;

    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * @var WinSW
     */
    protected $winsw;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * Create a new PHP CGI class instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @param  WinSwFactory  $winsw
     * @param  Configuration  $configuration
     * @return void
     */
    public function __construct(CommandLine $cli, Filesystem $files, WinSwFactory $winsw, Configuration $configuration)
    {
        $this->cli = $cli;
        $this->files = $files;
        $this->winsw = $winsw->make('phpcgiservice');
        $this->configuration = $configuration;
    }

    /**
     * Install and configure PHP CGI service.
     *
     * @return void
     */
    public function install()
    {
        info('Installing PHP-CGI service...');

        $this->installService();
    }

    /**
     * Install the Windows service.
     *
     * @return void
     */
    public function installService()
    {
        if ($this->winsw->installed()) {
            $this->winsw->uninstall();
        }

        $this->winsw->install([
            'PHP_PATH' => $this->findPhpPath(),
            'PHP_PORT' => $this->configuration->get('php_port', PhpCgi::PORT),
        ]);

        $this->winsw->restart();
    }

    /**
     * Uninstall the PHP CGI service.
     *
     * @return void
     */
    public function uninstall()
    {
        $this->winsw->uninstall();
    }

    /**
     * Restart the PHP CGI service.
     *
     * @return void
     */
    public function restart()
    {
        $this->winsw->restart();
    }

    /**
     * Stop the PHP CGI service.
     *
     * @return void
     */
    public function stop()
    {
        $this->winsw->stop();
    }

    /**
     * Find the PHP path.
     *
     * @return string
     */
    protected function findPhpPath(): string
    {
        if (! $php = (new PhpExecutableFinder)->find()) {
            $php = $this->cli->runOrExit('where php', function () {
                error('Failed to find PHP. Make sure it\'s added to the path environment variables.');
            });
        }

        return pathinfo(explode("\n", $php)[0], PATHINFO_DIRNAME);
    }
}
