<?php

namespace Valet;

class PhpCgiXdebug extends PhpCgi
{
    const PORT = 9002;

    /**
     * @inheritDoc
     */
    public function __construct(CommandLine $cli, Filesystem $files, WinSwFactory $winsw, Configuration $configuration)
    {
        parent::__construct($cli, $files, $winsw, $configuration);

        $this->winsw = $winsw->make('phpcgixdebugservice');
    }

    /**
     * Install and configure PHP CGI service.
     *
     * @return void
     */
    public function install()
    {
        info('Installing PHP-CGI Xdebug service...');

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
            'PHP_XDEBUG_PORT' => $this->configuration->get('php_xdebug_port', PhpCgiXdebug::PORT),
        ]);

        $this->winsw->restart();
    }
}
