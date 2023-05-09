<?php

namespace Tests;

use Valet\Configuration;
use Valet\Filesystem;
use function Valet\resolve;
use function Valet\user;
use Valet\Valet;

class ConfigurationTest extends TestCase
{
	/** @test */
	public function configuration_directory_is_created_if_it_doesnt_exist()
	{
		$this->mock(Filesystem::class)
			->shouldReceive('ensureDirExists')->once()->with(preg_replace('~/valet$~', '', Valet::homePath()), user())
			->shouldReceive('isDir')->andReturn(false)
			->shouldReceive('ensureDirExists')->once()->with(Valet::homePath(), user());

		resolve(Configuration::class)->createConfigurationDirectory();
	}

	/** @test */
	public function drivers_directory_is_created_with_sample_driver_if_it_doesnt_exist()
	{
		$this->partialMock(Filesystem::class)
			->shouldReceive('isDir')->with(Valet::homePath('Drivers'))->andReturn(false)
			->shouldReceive('mkdirAsUser')->with(Valet::homePath('Drivers'))
			->shouldReceive('putAsUser');

		resolve(Configuration::class)->createDriversDirectory();
	}

	/** @test */
	public function sites_directory_is_created_if_it_doesnt_exist()
	{
		$this->mock(Filesystem::class)
			->shouldReceive('ensureDirExists')->once()->with(Valet::homePath('Sites'), user());

		resolve(Configuration::class)->createSitesDirectory();
	}

	/** @test */
	public function extensions_directory_is_created_if_it_doesnt_exist()
	{
		$this->mock(Filesystem::class)
			->shouldReceive('ensureDirExists')->once()->with(Valet::homePath('Extensions'), user());

		resolve(Configuration::class)->createExtensionsDirectory();
	}

	/** @test */
	public function log_directory_is_created_with_log_files_if_it_doesnt_exist()
	{
		$this->mock(Filesystem::class)
			->shouldReceive('ensureDirExists')->once()->with(Valet::homePath('Log'), user())
			->shouldReceive('touch')->once()->with(Valet::homePath('Log\nginx-error.log'));

		resolve(Configuration::class)->createLogDirectory();
	}

	/** @test */
	public function certificates_directory_is_created_if_it_doesnt_exist()
	{
		$this->mock(Filesystem::class)
			->shouldReceive('ensureDirExists')->once()->with(Valet::homePath('Certificates'), user());

		resolve(Configuration::class)->createCertificatesDirectory();
	}

	/** @test */
	public function services_directory_is_created_if_it_doesnt_exist()
	{
		$this->mock(Filesystem::class)
			->shouldReceive('ensureDirExists')->once()->with(Valet::homePath('Services'), user());

		resolve(Configuration::class)->createServicesDirectory();
	}

	/** @test */
	public function xdebug_directory_is_created_if_it_doesnt_exist()
	{
		$this->mock(Filesystem::class)
			->shouldReceive('ensureDirExists')->once()->with(Valet::homePath('Xdebug'), user());

		resolve(Configuration::class)->createXdebugDirectory();
	}

	/** @test */
	public function base_configuration_is_created_if_it_doesnt_exist()
	{
		$this->mock(Filesystem::class)
			->shouldReceive('exists')->andReturn(false)
			->shouldReceive('putAsUser')
			->shouldReceive('get')->andReturn(json_encode(['tld' => 'test', 'php_port' => 9001]));

		resolve(Configuration::class)->writeBaseConfiguration();
	}

	/** @test */
	public function add_path_adds_a_path_to_the_paths_array_and_removes_duplicates()
	{
		$this->mock(Filesystem::class)
			->shouldReceive('get')->andReturn(json_encode([
				'paths' => ['path-1', 'path-2'],
			]))
			->shouldReceive('putAsUser')->with(Valet::homePath('config.json'), json_encode(
				['paths' => ['path-1', 'path-2', 'path-3']],
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
			).PHP_EOL);

		resolve(Configuration::class)->addPath('path-3');

		$this->mock(Filesystem::class)
			->shouldReceive('get')->andReturn(json_encode([
				'paths' => ['path-1', 'path-2', 'path-3'],
			]))
			->shouldReceive('putAsUser')->with(Valet::homePath('config.json'), json_encode(
				['paths' => ['path-1', 'path-2', 'path-3']],
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
			).PHP_EOL);

		resolve(Configuration::class)->addPath('path-3');
	}

	/** @test */
	public function paths_may_be_removed_from_the_configuration()
	{
		$this->mock(Filesystem::class)
			->shouldReceive('get')->andReturn(json_encode([
				'paths' => ['path-1', 'path-2'],
			]))
			->shouldReceive('putAsUser')->with(Valet::homePath('config.json'), json_encode(
				['paths' => ['path-1']],
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
			).PHP_EOL);

		resolve(Configuration::class)->removePath('path-2');
	}

	/** @test */
	public function prune_removes_directories_from_paths_that_no_longer_exist()
	{
		$this->mock(Filesystem::class)
			->shouldReceive('exists')->with(Valet::homePath('config.json'))->andReturn(true)
			->shouldReceive('get')->andReturn(json_encode([
				'paths' => ['path-1', 'path-2'],
			]))
			->shouldReceive('isDir')->with('path-1')->andReturn(true)
			->shouldReceive('isDir')->with('path-2')->andReturn(false)
			->shouldReceive('putAsUser')->with(Valet::homePath('config.json'), json_encode(
				['paths' => ['path-1']],
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
			).PHP_EOL);

		resolve(Configuration::class)->prune();
	}

	/** @test */
	public function prune_doesnt_execute_if_configuration_directory_doesnt_exist()
	{
		$this->mock(Filesystem::class)
			->shouldReceive('exists')->with(Valet::homePath('config.json'))->andReturn(false)
			->shouldReceive('get')->never()
			->shouldReceive('putAsUser')->never();

		resolve(Configuration::class)->prune();
	}

	/** @test */
	public function update_key_updates_the_specified_configuration_key()
	{
		$this->mock(Filesystem::class)
			->shouldReceive('exists')->with(Valet::homePath('config.json'))->andReturn(true)
			->shouldReceive('get')->andReturn(json_encode(['foo' => 'bar']))
			->shouldReceive('putAsUser')->with(Valet::homePath('config.json'), json_encode(
				['foo' => 'bar', 'bar' => 'baz'],
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
			).PHP_EOL);

		resolve(Configuration::class)->updateKey('bar', 'baz');
	}
}
