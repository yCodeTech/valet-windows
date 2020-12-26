<?php

namespace Valet;

use DomainException;

class Nginx
{
    const SERVICE = 'nginxservice';

    /**
     * @var CommandLine
     */
    protected $cli;

    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var WinSW
     */
    protected $winsw;

    /**
     * Create a new Nginx instance.
     *
     * @param CommandLine   $cli
     * @param Filesystem    $files
     * @param Configuration $configuration
     * @param Site          $site
     * @param WinSW         $winsw
     *
     * @return void
     */
    public function __construct(CommandLine $cli, Filesystem $files,
                         Configuration $configuration, Site $site, WinSwFactory $winsw)
    {
        $this->cli = $cli;
        $this->site = $site;
        $this->files = $files;
        $this->winsw = $winsw->make(static::SERVICE);
        $this->configuration = $configuration;
    }

    /**
     * Install the configuration files for Nginx.
     *
     * @return void
     */
    public function install()
    {
        info('Installing Nginx...');

        $this->installConfiguration();
        $this->installServer();
        $this->installNginxDirectory();
        $this->installService();
    }

    /**
     * Install the Nginx configuration file.
     *
     * @return void
     */
    public function installConfiguration()
    {
        $contents = $this->files->get(__DIR__.'/../stubs/nginx.conf');

        $this->files->putAsUser(
            $this->path('conf/nginx.conf'),
            str_replace(['VALET_USER', 'VALET_HOME_PATH'], [user(), VALET_HOME_PATH], $contents)
        );
    }

    /**
     * Install the Valet Nginx server configuration file.
     *
     * @return void
     */
    public function installServer()
    {
        $this->files->ensureDirExists($this->path('valet'));

        $this->files->putAsUser(
            $this->path('valet/valet.conf'),
            str_replace(
                ['VALET_HOME_PATH', 'VALET_SERVER_PATH', 'VALET_STATIC_PREFIX', 'HOME_PATH', 'PHP_PORT'],
                [VALET_HOME_PATH, VALET_SERVER_PATH, VALET_STATIC_PREFIX, $_SERVER['HOME'], $this->configuration->read()['php_port'] ?? PhpCgi::PORT],
                $this->files->get(__DIR__.'/../stubs/valet.conf')
            )
        );

        $this->files->putAsUser(
            $this->path().'/conf/fastcgi_params',
            $this->files->get(__DIR__.'/../stubs/fastcgi_params')
        );
    }

    /**
     * Install the Nginx configuration directory to the ~/.config/valet directory.
     *
     * This directory contains all site-specific Nginx servers.
     *
     * @return void
     */
    public function installNginxDirectory()
    {
        if (! $this->files->isDir($nginxDirectory = Valet::homePath('Nginx'))) {
            $this->files->mkdirAsUser($nginxDirectory);
        }

        $this->files->putAsUser($nginxDirectory.DIRECTORY_SEPARATOR.'.keep', "\n");

        $this->rewriteSecureNginxFiles();
    }

    /**
     * Check nginx.conf for errors.
     */
    private function lint()
    {
        // TODO
        // $this->cli->run(
        //     'sudo nginx -c '.$this->path('conf/nginx.conf').' -t',
        //     function ($exitCode, $outputMessage) {
        //         throw new DomainException("Nginx cannot start; please check your nginx.conf [$exitCode: $outputMessage].");
        //     }
        // );
    }

    /**
     * Generate fresh Nginx servers for existing secure sites.
     *
     * @return void
     */
    public function rewriteSecureNginxFiles()
    {
        $tld = $this->configuration->read()['tld'];

        $this->site->resecureForNewTld($tld, $tld);
    }

    /**
     * Install the Windows service.
     *
     * @return void
     */
    public function installService()
    {
        if (! $this->winsw->installed()) {
            $this->winsw->install([
                'NGINX_PATH' => $this->path(),
            ]);
        }

        $this->winsw->restart();
    }

    /**
     * Restart the Nginx service.
     *
     * @return void
     */
    public function restart()
    {
        // $this->stop();

        $this->winsw->restart();
    }

    /**
     * Stop the Nginx service.
     *
     * @return void
     */
    public function stop()
    {
        $this->winsw->stop();

        // $this->cli->run('cmd "/C taskkill /IM nginx.exe /F"');
    }

    /**
     * Prepare Nginx for uninstallation.
     *
     * @return void
     */
    public function uninstall()
    {
        $this->winsw->uninstall();
    }

    /**
     * Get the Nginx path.
     *
     * @param  string $path
     * @return string
     */
    public function path(string $path = ''): string
    {
        return realpath(__DIR__.'/../../bin/nginx').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}
