<?php

namespace Tests;

use Mockery as m;
use Symfony\Component\Process\PhpExecutableFinder;
use Valet\Configuration;
use Valet\PhpCgi;
use function Valet\resolve;
use Valet\WinSW;
use Valet\WinSwFactory;

class PhpCgiTest extends TestCase
{
    /** @test */
    public function install_php_cgi_service()
    {
        $this->mock(Configuration::class)
            ->shouldReceive('read')
            ->andReturn(['php_port' => 1234]);

        ($winsw = m::mock(WinSW::class))
            ->shouldReceive('installed')
                ->once()
                ->andReturn(false)
            ->shouldReceive('install')
                ->once()
                ->with([
                    'PHP_PATH' => $this->findPhpPath(),
                    'PHP_PORT' => 1234,
                ])
            ->shouldReceive('restart')
                ->once();

        $this->mock(WinSwFactory::class)
            ->shouldReceive('make')
                ->once()
                ->with(PhpCgi::SERVICE)
                ->andReturn($winsw);

        resolve(PhpCgi::class)->install();
    }

    /** @test */
    public function uninstall_php_cgi_service()
    {
        ($winsw = m::mock(WinSW::class))
            ->shouldReceive('uninstall')
            ->once();

        $this->mock(WinSwFactory::class)
            ->shouldReceive('make')
            ->andReturn($winsw);

        resolve(PhpCgi::class)->uninstall();
    }

    /** @test */
    public function restart_php_cgi_service()
    {
        ($winsw = m::mock(WinSW::class))
            ->shouldReceive('restart')
            ->once();

        $this->mock(WinSwFactory::class)
            ->shouldReceive('make')
            ->andReturn($winsw);

        resolve(PhpCgi::class)->restart();
    }

    /** @test */
    public function stop_php_cgi_service()
    {
        ($winsw = m::mock(WinSW::class))
            ->shouldReceive('stop')
            ->once();

        $this->mock(WinSwFactory::class)
            ->shouldReceive('make')
            ->andReturn($winsw);

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
