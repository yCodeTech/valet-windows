<?php

namespace Tests;

use Symfony\Component\Process\PhpExecutableFinder;
use Valet\Configuration;
use Valet\PhpCgi;
use function Valet\resolve;
use Valet\WinSW;

class PhpCgiTest extends TestCase
{
    /** @test */
    public function install_php_cgi_service()
    {
        $this->mock(Configuration::class)
            ->shouldReceive('read')
            ->andReturn(['php_port' => 1234]);

        $this->mock(WinSW::class)
            ->shouldReceive('install')->once()->with(PhpCgi::SERVICE, [
                'PHP_PATH' => $this->findPhpPath(),
                'PHP_PORT' => 1234,
            ])
            ->shouldReceive('restart')->once()->with(PhpCgi::SERVICE);

        resolve(PhpCgi::class)->install();
    }

    /** @test */
    public function uninstall_php_cgi_service()
    {
        $this->mock(WinSW::class)
            ->shouldReceive('uninstall')->once()->with(PhpCgi::SERVICE);

        resolve(PhpCgi::class)->uninstall();
    }

    /** @test */
    public function restart_php_cgi_service()
    {
        $this->mock(WinSW::class)
            ->shouldReceive('restart')->once()->with(PhpCgi::SERVICE);

        resolve(PhpCgi::class)->restart();
    }

    /** @test */
    public function stop_php_cgi_service()
    {
        $this->mock(WinSW::class)
            ->shouldReceive('stop')->once()->with(PhpCgi::SERVICE);

        resolve(PhpCgi::class)->stop();
    }

    /**
     * @return string
     */
    protected function findPhpPath(): string
    {
        $php = (new PhpExecutableFinder)->find();

        return pathinfo(explode("\n", $php)[0], PATHINFO_DIRNAME);
    }
}
