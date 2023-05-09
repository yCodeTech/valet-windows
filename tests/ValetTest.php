<?php

namespace Tests;

use Valet\CommandLine;
use function Valet\resolve;
use Valet\Valet;

class ValetTest extends TestCase
{
	/** @test */
	public function list_valet_services()
	{
		$this->mock(CommandLine::class)
			->shouldReceive('run')
				->once()
				->with('powershell -command "Get-Service -Name AcrylicDNSProxySvc"')
				->andReturn('Running  AcrylicDNSProxySvc        AcrylicDNSProxySvc')
			->shouldReceive('run')
				->once()
				->with('powershell -command "Get-Service -Name valet_nginx"')
				->andReturn('Running  valet_nginx        valet_nginx')
			->shouldReceive('run')
				->once()
				->with('powershell -command "Get-Service -Name valet_phpcgi"')
				->andReturn('Running  valet_phpcgi        valet_phpcgi')
			->shouldReceive('run')
				->once()
				->with('powershell -command "Get-Service -Name valet_phpcgi_xdebug"')
				->andReturn('Running  valet_phpcgi_xdebug        valet_phpcgi_xdebug');

		$services = resolve(Valet::class)->services();

		$this->assertCount(4, $services);

		$this->assertSame([
			[
				'service' => 'acrylic',
				'winname' => 'AcrylicDNSProxySvc',
				'status' => '<fg=green>running</>',
			],
			[
				'service' => 'nginx',
				'winname' => 'valet_nginx',
				'status' => '<fg=green>running</>',
			],
			[
				'service' => 'php',
				'winname' => 'valet_phpcgi',
				'status' => '<fg=green>running</>',
			],
			[
				'service' => 'php-xdebug',
				'winname' => 'valet_phpcgi_xdebug',
				'status' => '<fg=green>running</>',
			],
		], $services);
	}
}
