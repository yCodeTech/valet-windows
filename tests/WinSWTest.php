<?php

namespace Tests;

use Valet\CommandLine;
use Valet\Filesystem;
use function Valet\resolve;
use Valet\Valet;
use Valet\WinSW;
use Valet\WinSwFactory;

class WinSWTest extends TestCase
{
    /** @test */
    public function make_winsw()
    {
        $winsw = resolve(WinSwFactory::class)->make('testservice');

        $this->assertInstanceOf(WinSW::class, $winsw);
    }

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
                $this->assertSame('cmd "/C cd '.Valet::homePath('Services').' && "'.Valet::homePath('Services\testservice').'" install"', $command);
            })->once();

        $this->winsw('testservice')->install();
    }

    /** @test */
    public function stop_service()
    {
        $this->mock(CommandLine::class)
            ->shouldReceive('run')->andReturnUsing(function ($command) {
                $this->assertSame('cmd "/C cd '.Valet::homePath('Services').' && "'.Valet::homePath('Services\testservice').'" stop"', $command);
            })->once();

        $this->winsw('testservice')->stop();
    }

    /** @test */
    public function restart_service()
    {
        $this->mock(CommandLine::class)
            ->shouldReceive('run')->andReturnUsing(function ($command) {
                $this->assertSame('cmd "/C cd '.Valet::homePath('Services').' && "'.Valet::homePath('Services\testservice').'" restart"', $command);
            })->once();

        $this->winsw('testservice')->restart();
    }

    /** @test */
    public function uninstall_service()
    {
        $this->mock(CommandLine::class)
            ->shouldReceive('run')->once()
            ->shouldReceive('run')->andReturnUsing(function ($command) {
                $this->assertSame('cmd "/C cd '.Valet::homePath('Services').' && "'.Valet::homePath('Services\testservice').'" uninstall"', $command);
            })->once();

        $this->winsw('testservice')->uninstall();
    }

    /**
     * @param  string  $service
     * @return \Valet\WinSW
     */
    protected function winsw(string $service): WinSW
    {
        return resolve(WinSwFactory::class)->make($service);
    }
}
