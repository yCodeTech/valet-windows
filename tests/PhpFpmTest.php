<?php

use Illuminate\Container\Container;
use Valet\CommandLine;
use Valet\PhpFpm;

class PhpFpmTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SERVER['SUDO_USER'] = user();

        Container::setInstance(new Container());
    }

    public function tearDown()
    {
        exec('rm -rf '.__DIR__.'/output');
        mkdir(__DIR__.'/output');
        touch(__DIR__.'/output/.gitkeep');

        Mockery::close();
    }

    public function test_can_find_php_path()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runOrDie')->once()->with('where php', Mockery::type('callable'))->andReturn('C:\php\php.exe');

        swap(CommandLine::class, $cli);

        $phpfpm = resolve(PhpFpm::class);
        $this->assertEquals('C:\php', $phpfpm->findPhpPath());
    }

    public function test_can_find_first_php_path()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runOrDie')->once()->with('where php', Mockery::type('callable'))->andReturn(
            "C:\php\php.exe\nC:\php71\php.exe"
        );

        swap(CommandLine::class, $cli);

        $phpfpm = resolve(PhpFpm::class);
        $this->assertEquals('C:\php', $phpfpm->findPhpPath());
    }

    public function test_throws_exception_if_can_not_find_php_path()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not find PHP. Make sure it\'s added to the environment variables.');

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('runOrDie')->with('where php', Mockery::on(function ($arg) {
            $arg(123, 'message');
        }));

        swap(CommandLine::class, $cli);

        resolve(PhpFpm::class)->findPhpPath();
    }
}

class StubForUpdatingFpmConfigFiles extends PhpFpm
{
    public function fpmConfigPath()
    {
        return __DIR__.'/output/fpm.conf';
    }
}
