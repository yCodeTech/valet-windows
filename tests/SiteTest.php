<?php

use Valet\Site;
use Valet\Filesystem;
use function Valet\swap;
use function Valet\user;
use Valet\Configuration;
use function Valet\resolve;
use Illuminate\Container\Container;

class SiteTest extends PHPUnit_Framework_TestCase
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

    public function test_symlink_creates_symlink_to_given_path()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('ensureDirExists')->once()->with(VALET_HOME_PATH.'/Sites', user());
        $config = Mockery::mock(Configuration::class);
        $config->shouldReceive('prependPath')->once()->with(VALET_HOME_PATH.'/Sites');
        $files->shouldReceive('symlinkAsUser')->once()->with('target', VALET_HOME_PATH.'/Sites/link');

        swap(Filesystem::class, $files);
        swap(Configuration::class, $config);

        $linkPath = resolve(Site::class)->link('target', 'link');
        $this->assertSame(VALET_HOME_PATH.'/Sites/link', $linkPath);
    }

    public function test_unlink_removes_existing_symlink()
    {
        file_put_contents(__DIR__.'/output/file.out', 'test');
        symlink(__DIR__.'/output/file.out', __DIR__.'/output/link');
        $site = resolve(StubForRemovingLinks::class);
        $site->unlink('link');
        $this->assertFileNotExists(__DIR__.'/output/link');

        $site = resolve(StubForRemovingLinks::class);
        $site->unlink('link');
        $this->assertFileNotExists(__DIR__.'/output/link');
    }

    public function test_prune_links_removes_broken_symlinks_in_sites_path()
    {
        file_put_contents(__DIR__.'/output/file.out', 'test');
        symlink(__DIR__.'/output/file.out', __DIR__.'/output/link');
        unlink(__DIR__.'/output/file.out');
        $site = resolve(StubForRemovingLinks::class);
        $site->pruneLinks();
        $this->assertFileNotExists(__DIR__.'/output/link');
    }

    public function test_certificates_trim_tld_for_custom_tlds()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('scandir')->once()->andReturn([
            'threeletters.dev.crt',
            'fiveletters.local.crt',
        ]);

        swap(Filesystem::class, $files);

        $site = resolve(Site::class);
        $certs = $site->getCertificates('fake-cert-path')->flip();

        $this->assertEquals('threeletters', $certs->first());
        $this->assertEquals('fiveletters', $certs->last());
    }

    public function test_get_site_port()
    {
        $path = VALET_HOME_PATH.'/Nginx/example.test.conf';
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('exists')
            ->once()
            ->with($path)
            ->andReturn(false)
            ->shouldReceive('get')
            ->never();

        swap(Filesystem::class, $files);

        $site = resolve(Site::class);

        $this->assertEquals(80, $site->port('example.test'));
    }

    public function test_get_site_port_when_secured()
    {
        $path = VALET_HOME_PATH.'/Nginx/example.test.conf';
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('exists')
            ->once()
            ->with($path)
            ->andReturn(true)
            ->shouldReceive('get')
            ->once()
            ->with($path)
            ->andReturn(file_get_contents(__DIR__.'/../cli/stubs/secure.valet.conf'));

        swap(Filesystem::class, $files);

        $site = resolve(Site::class);

        $this->assertEquals(443, $site->port('example.test'));
    }
}

class StubForRemovingLinks extends Site
{
    public function sitesPath()
    {
        return __DIR__.'/output';
    }
}
