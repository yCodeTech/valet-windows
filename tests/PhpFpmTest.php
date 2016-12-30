<?php

use Illuminate\Container\Container;
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

    public function test_fpm_is_configured()
    {

    }
}

class StubForUpdatingFpmConfigFiles extends PhpFpm
{
    public function fpmConfigPath()
    {
        return __DIR__.'/output/fpm.conf';
    }
}
