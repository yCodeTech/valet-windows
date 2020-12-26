<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Tests\Support\TestProcess;

/**
 * @group integration
 */
class WindowsIntegrationTest extends TestCase
{
    /** @test */
    public function install_valet()
    {
        $this->exec('valet install')
            ->assertSuccessful()
            ->assertContains('Valet installed successfully');

        $this->exec('Get-Service -Name "valet_nginx"')
            ->assertSuccessful()
            ->assertContains('Running  valet_nginx');

        $this->exec('Get-Service -Name "valet_phpcgi"')
            ->assertSuccessful()
            ->assertContains('Running  valet_phpcgi');

        $this->exec('Get-Service -Name "AcrylicDNSProxySvc"')
            ->assertSuccessful()
            ->assertContains('Running  AcrylicDNSProxySvc');

        $this->exec('curl.exe --max-time 5 http://localhost')
            ->assertSuccessful()
            ->assertContains('<title>Valet - Not Found</title>');

        $this->exec('valet')
            ->assertSuccessful();

        $this->exec('ls ~/.config/valet')
            ->assertContains('Certificates')
            ->assertContains('Drivers')
            ->assertContains('Extensions')
            ->assertContains('Log')
            ->assertContains('Nginx')
            ->assertContains('Sites')
            ->assertContains('AcrylicHosts.txt')
            ->assertContains('config.json');
    }

    /**
     * @param  string|array $command
     * @return \Tests\Support\TestProcess
     */
    protected function exec($command): TestProcess
    {
        if (is_array($command)) {
            $command = implode(';', $command);
        }

        $process = Process::fromShellCommandline("powershell -Command \"{$command}\"");
        $process->setTimeout(30)->run();

        return new TestProcess($process);
    }
}
