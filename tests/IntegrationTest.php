<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\Support\DockerContainer;

/**
 * @group integration
 */
class IntegrationTest extends TestCase
{
	/**
	 * @var \Tests\Support\DockerContainerInstance
	 */
	protected $container;

	public function setUp(): void
	{
		$this->container = DockerContainer::create('cretueusebiu/valet-windows')
			->command('ping -t localhost')
			->start();
	}

	public function tearDown(): void
	{
		if ($this->container) {
			$this->container->stop();
		}
	}

	/** @test */
	public function install_valet()
	{
		// $this->container->exec('valet install')
		//     ->assertSuccessful()
		//     ->assertContains('Valet installed successfully');

		$this->container->exec('Get-Service -Name "valet_nginx"')
			->assertSuccessful()
			->assertContains('Running  valet_nginx');

		$this->container->exec('Get-Service -Name "valet_phpcgi"')
			->assertSuccessful()
			->assertContains('Running  valet_phpcgi');

		$this->container->exec('Get-Service -Name "AcrylicDNSProxySvc"')
			->assertSuccessful()
			->assertContains('Running  AcrylicDNSProxySvc');

		$this->container->exec('curl.exe --max-time 5 http://localhost')
			->assertSuccessful()
			->assertContains('<title>Valet - Not Found</title>');

		$this->container->exec('valet')
			->assertSuccessful();

		$this->container->exec('ls ~/.config/valet')
			->assertContains('Certificates')
			->assertContains('Drivers')
			->assertContains('Extensions')
			->assertContains('Log')
			->assertContains('Nginx')
			->assertContains('Sites')
			->assertContains('AcrylicHosts.txt')
			->assertContains('config.json');
	}

	/** @test */
	public function configure_tld()
	{
		// $this->container->exec('valet install')->assertSuccessful();

		$this->container->exec('valet tld')
			->assertSuccessful()
			->assertContains('test');

		$this->container->exec('valet tld foo')
			->assertSuccessful()
			->assertContains('Your Valet TLD has been updated to [foo].');

		$this->container->exec('cat ~/.config/valet/config.json')
			->assertSuccessful()
			->decodeOuputJson()
			->assertFragment(['tld' => 'foo']);
	}

	/** @test */
	public function park_directory()
	{
		// $this->container->exec('valet install')->assertSuccessful();

		$this->container->exec('mkdir C:/Code')
			->assertSuccessful();

		$this->container->exec('valet park C:/Code')
			->assertSuccessful()
			->assertContains('The [C:/Code] directory has been added to Valet\'s paths.');

		$this->container->exec('cat ~/.config/valet/config.json')
			->assertSuccessful()
			->decodeOuputJson()
			->assertFragment(['paths' => ['C:/Code']]);

		$this->container->exec([
			'mkdir C:/Code/laravel',
			"Set-Content -Path 'C:/Code/laravel/index.html' -Value 'hello world'",
		])
			->assertSuccessful();

		$this->container->exec('curl.exe --max-time 5 http://laravel.test')
			->assertSuccessful()
			->assertContains('hello world');
	}

	/** @test */
	public function list_sites_within_parked_paths()
	{
		// $this->container->exec('valet install')->assertSuccessful();

		$this->container->exec('mkdir C:/Code/laravel')
			->assertSuccessful();

		$this->container->exec('valet park C:/Code')
			->assertSuccessful();

		$this->container->exec('valet parked')
			->assertSuccessful()
			->assertContains('| laravel |     | http://laravel.test | C:\Code\laravel |');
	}

	/** @test */
	public function forget_parked_path()
	{
		// $this->container->exec('valet install')->assertSuccessful();

		$this->container->exec('mkdir C:/Code')
			->assertSuccessful();

		$this->container->exec('valet park C:/Code')
			->assertSuccessful();

		$this->container->exec('valet forget C:/Code')
			->assertSuccessful()
			->assertContains('The [C:/Code] directory has been removed from Valet\'s paths.');

		$this->container->exec('cat ~/.config/valet/config.json')
			->assertSuccessful()
			->decodeOuputJson()
			->assertFragment(['paths' => []]);
	}

	/** @test */
	public function add_symbolic_link()
	{
		// $this->container->exec('valet install')->assertSuccessful();

		$this->container->exec('mkdir C:/Code/laravel')
			->assertSuccessful();

		$this->container->exec('cd C:/Code/laravel; valet link laravel-api')
			->assertSuccessful()
			->assertContains('A [laravel-api] symbolic link has been created in [C:/Users/ContainerAdministrator/.config/valet/Sites/laravel-api].');

		$this->container->exec('cat ~/.config/valet/config.json')
			->assertSuccessful()
			->decodeOuputJson()
			->assertFragment(['paths' => ['C:/Users/ContainerAdministrator/.config/valet/Sites']]);

		$this->container->exec('Get-Item -Path ~/.config/valet/Sites/laravel-api')
			->assertSuccessful()
			->assertContains('d----l');

		$this->container->exec([
			'cd C:/Code/laravel',
			"Set-Content -Path 'C:/Code/laravel/index.html' -Value 'hello world'",
		])
			->assertSuccessful();

		$this->container->exec('curl.exe --max-time 5 http://laravel-api.test')
			->assertSuccessful()
			->assertContains('hello world');
	}

	/** @test */
	public function list_symbolic_links()
	{
		// $this->container->exec('valet install')->assertSuccessful();

		$this->container->exec('mkdir C:/Code/laravel')
			->assertSuccessful();

		$this->container->exec('cd C:/Code/laravel; valet link laravel-api')
			->assertSuccessful();

		$this->container->exec('valet links')
			->assertSuccessful()
			->assertContains('| laravel-api |     | http://laravel-api.test | C:\Code\laravel |');
	}

	/** @test */
	public function remove_symbolic_link()
	{
		// $this->container->exec('valet install')->assertSuccessful();

		$this->container->exec('mkdir C:/Code/laravel')
			->assertSuccessful();

		$this->container->exec('cd C:/Code/laravel; valet link laravel-api')
			->assertSuccessful();

		$this->container->exec('valet unlink laravel-api')
			->assertSuccessful()
			->assertContains('The [laravel-api] symbolic link has been removed.');

		$this->container->exec('valet links')
			->assertSuccessful()
			->assertNotContains('laravel-api');
	}

	/** @test */
	public function secure_domain()
	{
		// $this->container->exec('valet install')->assertSuccessful();

		$this->container->exec('valet secure laravel')
			->assertSuccessful()
			->assertContains('The [laravel.test] site has been secured with a fresh TLS certificate.');

		$this->container->exec('ls ~/.config/valet/Certificates')
			->assertContains('laravel.test.crt')
			->assertContains('laravel.test.csr')
			->assertContains('laravel.test.key');

		$this->container->exec('Test-Path ~/.config/valet/Nginx/laravel.test.conf')
			->assertSuccessful()
			->assertContains('True');

		$this->container->exec([
			'mkdir C:/Code/laravel', 'cd C:/Code/laravel', 'valet link',
			"Set-Content -Path 'C:/Code/laravel/index.html' -Value 'hello world'",
		])
			->assertSuccessful();

		$this->container->exec('curl.exe --max-time 5 https://laravel.test')
			->assertSuccessful()
			->assertContains('hello world');

		$this->container->exec('curl.exe -I --max-time 5 http://laravel.test')
			->assertSuccessful()
			->assertContains('HTTP/1.1 302 Moved Temporarily')
			->assertContains('Location: https://laravel.test/');
	}

	/** @test */
	public function unsecure_domain()
	{
		// $this->container->exec('valet install')->assertSuccessful();

		$this->container->exec([
			'mkdir C:/Code/laravel', 'cd C:/Code/laravel', 'valet link',
			"Set-Content -Path 'C:/Code/laravel/index.html' -Value 'hello world'",
		])
			->assertSuccessful();

		$this->container->exec('valet secure laravel')
			->assertSuccessful();

		$this->container->exec('valet unsecure laravel')
			->assertSuccessful()
			->assertContains('The [laravel.test] site will now serve traffic over HTTP.');

		$this->container->exec('Test-Path ~/.config/valet/Nginx/laravel.test.conf')
			->assertSuccessful()
			->assertContains('False');

		$this->container->exec('curl.exe --max-time 5 http://laravel.test')
			->assertSuccessful()
			->assertContains('hello world');

		$this->container->exec('curl.exe --max-time 5 https://laravel.test')
			->assertContains('Failed to connect to laravel.test port 443');
	}

	/** @test */
	public function unsecure_all_domains()
	{
		// $this->container->exec('valet install')->assertSuccessful();

		$this->container->exec([
			'mkdir C:/Code/laravel1',
			'mkdir C:/Code/laravel2',
			'valet park C:/Code',
			'valet secure laravel1',
			'valet secure laravel2',
		])
			->assertSuccessful();

		$this->container->exec('valet unsecure --all')
			->assertSuccessful()
			->assertContains('unsecure --all was successful');

		$this->container->exec('Test-Path ~/.config/valet/Nginx/laravel1.test.conf')
			->assertSuccessful()
			->assertContains('False');

		$this->container->exec('Test-Path ~/.config/valet/Nginx/laravel2.test.conf')
			->assertSuccessful()
			->assertContains('False');
	}

	/** @test */
	public function proxying_services()
	{
		// $this->container->exec('valet install')->assertSuccessful();

		$this->container->exec('valet proxy elasticsearch http://127.0.0.1:9200')
			->assertSuccessful()
			->assertContains('Valet will now proxy [https://elasticsearch.test] traffic to [http://127.0.0.1:9200].');

		$this->container->exec('Test-Path ~/.config/valet/Nginx/elasticsearch.test.conf')
			->assertSuccessful()
			->assertContains('True');

		$this->container->exec('valet proxies')
			->assertSuccessful()
			->assertContains('| elasticsearch |  X  | https://elasticsearch.test | http://127.0.0.1:9200 |');

		$this->container->exec('valet unproxy elasticsearch')
			->assertSuccessful()
			->assertContains('Valet will no longer proxy [https://elasticsearch.test].');

		$this->container->exec('Test-Path ~/.config/valet/Nginx/elasticsearch.test.conf')
			->assertSuccessful()
			->assertContains('False');
	}

	/** @test */
	public function determine_valet_driver()
	{
		// $this->container->exec('valet install')->assertSuccessful();

		$this->container->exec([
			'mkdir C:/Code/laravel',
			"Set-Content -Path 'C:/Code/laravel/index.html' -Value 'hello world'",
		])
			->assertSuccessful();

		$this->container->exec('cd C:/Code/laravel; valet which')
			->assertSuccessful()
			->assertContains('This site is served by [BasicValetDriver].');
	}

	/** @test */
	public function list_paths()
	{
		// $this->container->exec('valet install')->assertSuccessful();

		$this->container->exec([
			'mkdir C:/Code',
			'valet park C:/Code',
		])
			->assertSuccessful();

		$this->container->exec('valet paths')
			->assertSuccessful()
			->assertContains('"C:/Code"');
	}

	/** @test */
	public function start_daemon_services()
	{
		// $this->container->exec('valet install')->assertSuccessful();

		$this->container->exec([
			'Stop-Service -Name "valet_nginx"',
			'Stop-Service -Name "valet_phpcgi"',
			'Stop-Service -Name "AcrylicDNSProxySvc"',
		])
			->assertSuccessful();

		$this->container->exec('valet start')
			->assertSuccessful()
			->assertContains('Valet services have been started.');

		$this->container->exec('Get-Service -Name "valet_nginx"')
			->assertSuccessful()
			->assertContains('Running  valet_nginx');

		$this->container->exec('Get-Service -Name "valet_phpcgi"')
			->assertSuccessful()
			->assertContains('Running  valet_phpcgi');

		$this->container->exec('Get-Service -Name "AcrylicDNSProxySvc"')
			->assertSuccessful()
			->assertContains('Running  AcrylicDNSProxySvc');

		$this->container->exec('valet start acrylic')
			->assertSuccessful()
			->assertContains('Acrylic DNS has been started.');

		$this->container->exec('valet start nginx')
			->assertSuccessful()
			->assertContains('Nginx has been started.');

		$this->container->exec('valet start php')
			->assertSuccessful()
			->assertContains('PHP has been started.');
	}

	/** @test */
	public function stop_daemon_services()
	{
		$this->container->exec('valet stop')
			->assertSuccessful()
			->assertContains('Valet services have been stopped.');

		$this->container->exec('Get-Service -Name "valet_nginx"')
			->assertSuccessful()
			->assertContains('Stopped  valet_nginx');

		$this->container->exec('Get-Service -Name "valet_phpcgi"')
			->assertSuccessful()
			->assertContains('Stopped  valet_phpcgi');

		$this->container->exec('Get-Service -Name "AcrylicDNSProxySvc"')
			->assertSuccessful()
			->assertContains('Stopped  AcrylicDNSProxySvc');

		$this->container->exec('valet stop acrylic')
			->assertSuccessful()
			->assertContains('Acrylic DNS has been stopped.');

		$this->container->exec('valet stop nginx')
			->assertSuccessful()
			->assertContains('Nginx has been stopped.');

		$this->container->exec('valet stop php')
			->assertSuccessful()
			->assertContains('PHP has been stopped.');
	}

	/** @test */
	public function uninstall_valet()
	{
		// $this->container->exec('valet install')->assertSuccessful();

		$this->container->exec('valet uninstall --force')
			->assertSuccessful()
			->assertContains('Valet has been removed from your system.');

		$this->container->exec('Get-Service -Name "valet_nginx"')
			->assertContains("Cannot find any service with service name 'valet_nginx'");

		$this->container->exec('Get-Service -Name "valet_phpcgi"')
			->assertContains("Cannot find any service with service name 'valet_phpcgi'");

		$this->container->exec('Get-Service -Name "AcrylicDNSProxySvc"')
			->assertContains("Cannot find any service with service name 'AcrylicDNSProxySvc'");

		$this->container->exec('Test-Path ~/.config/valet')
			->assertContains('True');

		$this->container->exec('valet uninstall --force --purge-config');

		$this->container->exec('Test-Path ~/.config/valet')
			->assertContains('False');
	}
}
