<?php

namespace Tests;

use Valet\CommandLine;
use Valet\Filesystem;
use function Valet\resolve;
use Valet\Valet;
use Valet\WinSW;

class WinSWTest extends TestCase
{
    /** @test */
    public function install_service()
    {
        $this->mock(Filesystem::class)
            ->shouldReceive('get')->andReturnUsing(function ($path) {
                $this->assertSame(realpath(__DIR__.'/../cli/Valet').'/../stubs/testservice.xml', $path);
            })->once()
            ->shouldReceive('put')->andReturnUsing(function ($path) {
                $this->assertSame(Valet::homePath('Services\testservice.xml'), $path);
            })->once()
            ->shouldReceive('copy')->andReturnUsing(function ($from, $to) {
                $this->assertSame(realpath(__DIR__.'/../bin/winsw/WinSW.NET4.exe'), $from);
                $this->assertSame(Valet::homePath('Services\testservice.exe'), $to);
            })->once();

        $this->mock(CommandLine::class)
            ->shouldReceive('runOrExit')->andReturnUsing(function ($command) {
                $this->assertSame('cmd "/C cd '.Valet::homePath('Services').' && testservice install"', $command);
            })->once();

        resolve(WinSW::class)->install('testservice');
    }

    /** @test */
    public function stop_service()
    {
        $this->mock(CommandLine::class)
            ->shouldReceive('run')->andReturnUsing(function ($command) {
                $this->assertSame('cmd "/C cd '.Valet::homePath('Services').' && testservice stop"', $command);
            })->once();

        resolve(WinSW::class)->stop('testservice');
    }

    /** @test */
    public function restart_service()
    {
        $this->mock(CommandLine::class)
            ->shouldReceive('run')->once()
            ->shouldReceive('run')->andReturnUsing(function ($command) {
                $this->assertSame('cmd "/C cd '.Valet::homePath('Services').' && testservice start"', $command);
            })->once();

        resolve(WinSW::class)->restart('testservice');
    }

    /** @test */
    public function uninstall_service()
    {
        $this->mock(CommandLine::class)
            ->shouldReceive('run')->once()
            ->shouldReceive('run')->andReturnUsing(function ($command) {
                $this->assertSame('cmd "/C cd '.Valet::homePath('Services').' && testservice uninstall"', $command);
            })->once();

        resolve(WinSW::class)->uninstall('testservice');
    }
}
