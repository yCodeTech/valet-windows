<?php

namespace Tests;

use Valet\CommandLine;
use Valet\Configuration;
use Valet\Filesystem;
use Valet\Nginx;
use function Valet\resolve;
use Valet\Site;
use Valet\Valet;
use Valet\WinSW;

class NginxTest extends TestCase
{
    /** @test */
    public function install_nginx_configuration_places_nginx_base_configuration_in_proper_location()
    {
        $this->partialMock(Filesystem::class)
            ->shouldReceive('putAsUser')->andReturnUsing(function ($path, $contents) {
                $this->assertSame(realpath(__DIR__.'/../bin/nginx').'\conf/nginx.conf', $path);
                $this->assertStringContainsString('include "'.Valet::homePath().'/Nginx/*', $contents);
            })->once();

        resolve(Nginx::class)->installConfiguration();
    }

    /** @test */
    public function install_nginx_directories_creates_location_for_site_specific_configuration()
    {
        $this->mock(Filesystem::class)
            ->shouldReceive('isDir')->with(Valet::homePath('Nginx'))->andReturn(false)
            ->shouldReceive('mkdirAsUser')->with(Valet::homePath('Nginx'))->once()
            ->shouldReceive('putAsUser')->with(Valet::homePath('Nginx\.keep'), "\n")->once();

        $this->mock(Configuration::class)
            ->shouldReceive('read')
            ->andReturn(['tld' => 'test']);

        $this->mock(Site::class)
            ->shouldReceive('resecureForNewTld');

        resolve(Nginx::class)->installNginxDirectory();
    }

    /** @test */
    public function nginx_directory_is_never_created_if_it_already_exists()
    {
        $this->mock(Filesystem::class)
            ->shouldReceive('isDir')->with(Valet::homePath('Nginx'))->andReturn(true)
            ->shouldReceive('mkdirAsUser')->never()
            ->shouldReceive('putAsUser')->with(Valet::homePath('Nginx\.keep'), "\n")->once();

        $this->mock(Configuration::class)
            ->shouldReceive('read')
            ->andReturn(['tld' => 'test']);

        $this->mock(Site::class)
            ->shouldReceive('resecureForNewTld');

        resolve(Nginx::class)->installNginxDirectory();
    }

    /** @test */
    public function install_nginx_directories_rewrites_secure_nginx_files()
    {
        $this->mock(Filesystem::class)
            ->shouldReceive('isDir')->with(Valet::homePath('Nginx'))->andReturn(false)
            ->shouldReceive('mkdirAsUser')->with(Valet::homePath('Nginx'))->once()
            ->shouldReceive('putAsUser')->with(Valet::homePath('Nginx\.keep'), "\n")->once();

        $this->mock(Configuration::class)
            ->shouldReceive('read')
            ->andReturn(['tld' => 'test']);

        $this->mock(Site::class)
            ->shouldReceive('resecureForNewTld', ['test', 'test']);

        resolve(Nginx::class)->installNginxDirectory();
    }

    /** @test */
    public function install_nginx_service()
    {
        $this->mock(WinSW::class)
            ->shouldReceive('uninstall')->once()->with(Nginx::SERVICE)
            ->shouldReceive('install')->once()->with(Nginx::SERVICE, [
                'NGINX_PATH' => realpath(__DIR__.'/../bin/nginx'),
            ]);

        resolve(Nginx::class)->installService();
    }

    /** @test */
    public function restart_nginx_service()
    {
        $this->mock(CommandLine::class)
            ->shouldReceive('run')->once()->with('cmd "/C taskkill /IM nginx.exe /F"');

        $this->mock(WinSW::class)
            ->shouldReceive('stop')->once()->with(Nginx::SERVICE)
            ->shouldReceive('restart')->once()->with(Nginx::SERVICE);

        resolve(Nginx::class)->restart();
    }

    /** @test */
    public function stop_nginx_service()
    {
        $this->mock(CommandLine::class)
            ->shouldReceive('run')->once()->with('cmd "/C taskkill /IM nginx.exe /F"');

        $this->mock(WinSW::class)
            ->shouldReceive('stop')->once()->with(Nginx::SERVICE);

        resolve(Nginx::class)->stop();
    }

    /** @test */
    public function uninstall_nginx_service()
    {
        $this->mock(WinSW::class)
            ->shouldReceive('uninstall')->once()->with(Nginx::SERVICE);

        resolve(Nginx::class)->uninstall();
    }
}
