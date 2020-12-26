<?php

namespace Tests\Support;

use Symfony\Component\Process\Process;
use Spatie\Docker\DockerContainerInstance as SpatieDockerContainerInstance;

class DockerContainerInstance extends SpatieDockerContainerInstance
{
    /**
     * @param  string|array $command
     * @return \Tests\Support\TestProcess
     */
    public function exec($command): TestProcess
    {
        if (is_array($command)) {
            $command = implode(';', $command);
        }

        $fullCommand = "docker exec --interactive {$this->getShortDockerIdentifier()} powershell -Command \"{$command}\"";

        $process = Process::fromShellCommandline($fullCommand);

        $process->setTimeout(30)->run();

        return new TestProcess($process);
    }
}
