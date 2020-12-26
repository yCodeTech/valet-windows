<?php

namespace Valet;

use Symfony\Component\Process\PhpExecutableFinder;

class PhpCgi
{
    const PORT = 9001;
    const SERVICE = 'phpcgiservice';

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
     * @param  CommandLine   $cli
     * @param  Filesystem    $files
     * @param  WinSW         $winsw
     * @param  Configuration $configuration
     * @return void
     */
    public function __construct(CommandLine $cli, Filesystem $files, WinSW $winsw, Configuration $configuration)
    {
        $this->cli = $cli;
        $this->files = $files;
        $this->winsw = $winsw;
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

        $this->winsw->install(static::SERVICE, [
            'PHP_PATH' => $this->findPhpPath(),
            'PHP_PORT' => $this->configuration->read()['php_port'] ?? PhpCgi::PORT,
        ]);

        $this->restart();
    }

    /**
     * Uninstall the PHP CGI service.
     *
     * @return void
     */
    public function uninstall()
    {
        $this->winsw->uninstall(static::SERVICE);
    }

    /**
     * Restart the PHP CGI service.
     *
     * @return void
     */
    public function restart()
    {
        $this->winsw->restart(static::SERVICE);
    }

    /**
     * Stop the PHP CGI service.
     *
     * @return void
     */
    public function stop()
    {
        $this->winsw->stop(static::SERVICE);
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
