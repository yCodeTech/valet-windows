<?php

use Illuminate\Container\Container;
use Valet\CommandLine;
use Valet\Filesystem;
use function Valet\resolve;
use function Valet\swap;
use function Valet\user;
use Valet\WinSW;

class WinSWTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SERVER['SUDO_USER'] = user();

        Container::setInstance(new Container());
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function test_install_service()
    {
        $files = Mockery::mock(Filesystem::class);

        $files->shouldReceive('get')->andReturnUsing(function ($path) {
            $this->assertSame(realpath(__DIR__.'/../cli/Valet').'/../stubs/testservice.xml', $path);
        })->once();

        $files->shouldReceive('putAsUser')->andReturnUsing(function ($path) {
            $this->assertSame(VALET_HOME_PATH.'/Services/testservice.xml', $path);
        })->once();

        $files->shouldReceive('copy')->andReturnUsing(function ($from, $to) {
            $this->assertSame(realpath(__DIR__.'/../bin').'/winsw.exe', $from);
            $this->assertSame(VALET_HOME_PATH.'/Services/testservice.exe', $to);
        })->once();

        $cli = Mockery::mock(CommandLine::class);

        $cli->shouldReceive('runOrDie')->andReturnUsing(function ($command) {
            $this->assertSame('cmd "/C cd '.VALET_HOME_PATH.'\Services && testservice install"', $command);
        })->once();

        swap(CommandLine::class, $cli);
        swap(Filesystem::class, $files);

        resolve(WinSW::class)->install('testservice');
    }

    public function test_stop_service()
    {
        $cli = Mockery::mock(CommandLine::class);

        $cli->shouldReceive('run')->andReturnUsing(function ($command) {
            $this->assertSame('cmd "/C cd '.VALET_HOME_PATH.'\Services && testservice stop"', $command);
        })->once();

        swap(CommandLine::class, $cli);

        resolve(WinSW::class)->stop('testservice');
    }

    public function test_restart_service()
    {
        $cli = Mockery::mock(CommandLine::class);

        $cli->shouldReceive('run')->andReturnUsing(function ($command) {
            $this->assertSame('cmd "/C cd '.VALET_HOME_PATH.'\Services && testservice start"', $command);
        })->once();

        $winsw = Mockery::mock(WinSW::class.'[stop]', [$cli, resolve(Filesystem::class)]);
        $winsw->shouldReceive('stop')->with('testservice')->once();

        swap(WinSW::class, $winsw);

        resolve(WinSW::class)->restart('testservice');
    }

    public function test_uninstall_service()
    {
        $cli = Mockery::mock(CommandLine::class);

        $cli->shouldReceive('run')->andReturnUsing(function ($command) {
            $this->assertSame('cmd "/C cd '.VALET_HOME_PATH.'\Services && testservice uninstall"', $command);
        })->once();

        $winsw = Mockery::mock(WinSW::class.'[stop]', [$cli, resolve(Filesystem::class)]);
        $winsw->shouldReceive('stop')->with('testservice')->once();

        swap(WinSW::class, $winsw);

        resolve(WinSW::class)->uninstall('testservice');
    }
}
