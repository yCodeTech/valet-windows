<?php

namespace Tests;

use Valet\Filesystem;

class FilesystemTest extends TestCase
{
	public function tearDown(): void
	{
		parent::tearDown();

		exec('cmd /C rmdir /s /q "'.__DIR__.DIRECTORY_SEPARATOR.'output"');
		mkdir(__DIR__.'/output');
		touch(__DIR__.'/output/.gitkeep');
	}

	/** @test */
	public function remove_broken_links_removes_broken_symlinks()
	{
		$files = new Filesystem();

		file_put_contents(__DIR__.'/output/file.out', 'test');
		symlink(__DIR__.'/output/file.out', __DIR__.'/output/file.link');
		$this->assertFileExists(__DIR__.'/output/file.link');
		unlink(__DIR__.'/output/file.out');
		$files->removeBrokenLinksAt(__DIR__.'/output');
		$this->assertFileDoesNotExist(__DIR__.'/output/file.link');
	}
}
