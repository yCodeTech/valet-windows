<?php

namespace Tests;

use Tests\Support\FakeProcessOutput;
use Valet;
use Valet\Acrylic;
use Valet\CommandLine;
use Valet\Filesystem;
use function Valet\resolve;

class AcrylicTest extends TestCase
{
    /** @test */
    public function install_acrylic_service()
    {
        $this->partialMock(Filesystem::class)
            ->shouldReceive('put')->once()->andReturnUsing(function ($path, $contents) {
                $this->assertSame($this->path('AcrylicHosts.txt'), $path);
                $this->assertTrue(strpos($contents, 'test') !== false);
                $this->assertTrue(strpos($contents, Valet::homePath()) !== false);
            })
            ->shouldReceive('exists')->with(Valet::homePath('AcrylicHosts.txt'))->once()->andReturn(false)
            ->shouldReceive('putAsUser')->once()->andReturnUsing(function ($path, $contents) {
                $this->assertSame(Valet::homePath('AcrylicHosts.txt'), $path);
                $this->assertSame(PHP_EOL, $contents);
            });

        $this->mock(CommandLine::class)
            ->shouldReceive('powershell')->once()->with('Get-Service -Name "AcrylicDNSProxySvc"')->andReturn(FakeProcessOutput::unsuccessfull())
            ->shouldReceive('powershell')->once()->with('(Get-NetIPAddress -AddressFamily IPv4).InterfaceIndex | ForEach-Object {Set-DnsClientServerAddress -InterfaceIndex $_ -ServerAddresses (\"127.0.0.1\", \"8.8.8.8\")};(Get-NetIPAddress -AddressFamily IPv6).InterfaceIndex | ForEach-Object {Set-DnsClientServerAddress -InterfaceIndex $_ -ServerAddresses (\"::1\", \"2001:4860:4860::8888\")}')
            ->shouldReceive('runOrExit')->once()->andReturnUsing(function ($command) {
                $this->assertSame('cmd /C "'.$this->path('AcrylicUI.exe').'" InstallAcrylicService', $command);
            })
            ->shouldReceive('run')->once()->with('cmd "/C ipconfig /flushdns"');

        resolve(Acrylic::class)->install();
    }

    /** @test */
    public function update_acrylic_tld()
    {
        $this->mock(CommandLine::class)
            ->shouldReceive('run');

        $this->partialMock(Filesystem::class)
            ->shouldReceive('put')->once()->andReturnUsing(function ($path, $contents) {
                $this->assertTrue(strpos($contents, 'app') !== false);
            })
            ->shouldReceive('exists')->andReturn(true);

        resolve(Acrylic::class)->updateTld('app');
    }

    /** @test */
    public function start_acrylic_service()
    {
        $this->mock(CommandLine::class)
            ->shouldReceive('runOrExit')->once()->andReturnUsing(function ($command) {
                $this->assertSame('cmd /C "'.$this->path('AcrylicUI.exe').'" StartAcrylicService', $command);
            })
            ->shouldReceive('run')->once()->with('cmd "/C ipconfig /flushdns"');

        resolve(Acrylic::class)->start();
    }

    /** @test */
    public function restart_acrylic_service()
    {
        $this->mock(CommandLine::class)
            ->shouldReceive('run')->once()->andReturnUsing(function ($command) {
                $this->assertSame('cmd /C "'.$this->path('AcrylicUI.exe').'" RestartAcrylicService', $command);
            })
            ->shouldReceive('run')->once()->with('cmd "/C ipconfig /flushdns"');

        resolve(Acrylic::class)->restart();
    }

    /** @test */
    public function uninstall_acrylic_service()
    {
        $this->mock(CommandLine::class)
            ->shouldReceive('powershell')->once()->with('Get-Service -Name "AcrylicDNSProxySvc"')->andReturn(FakeProcessOutput::successfull())
            ->shouldReceive('powershell')->once()->with('(Get-NetIPAddress -AddressFamily IPv4).InterfaceIndex | ForEach-Object {Set-DnsClientServerAddress -InterfaceIndex $_ -ResetServerAddresses};(Get-NetIPAddress -AddressFamily IPv6).InterfaceIndex | ForEach-Object {Set-DnsClientServerAddress -InterfaceIndex $_ -ResetServerAddresses}')
            ->shouldReceive('run')->twice()
            ->shouldReceive('run')->once()->andReturnUsing(function ($command) {
                $this->assertSame('cmd /C "'.$this->path('AcrylicUI.exe').'" UninstallAcrylicService', $command);
            })
            ->shouldReceive('run')->once();

        resolve(Acrylic::class)->uninstall();
    }

    /**
     * @param  string  $path
     * @return string
     */
    protected function path(string $path = ''): string
    {
        return resolve(Acrylic::class)->path($path);
    }
}
