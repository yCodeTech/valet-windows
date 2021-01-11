<?php

namespace Tests\Support;

use Spatie\Docker\DockerContainer as SpatieDockerContainer;

class DockerContainer extends SpatieDockerContainer
{
    public string $command = '';

    public function command(string $command): self
    {
        $this->command = $command;

        return $this;
    }

    public function getStartCommand(): string
    {
        return parent::getStartCommand().' '.$this->command;
    }

    public static function create(...$args): self
    {
        return new static(...$args);
    }

    public function start(): DockerContainerInstance
    {
        $containerInstance = parent::start();

        return new DockerContainerInstance(
            $this,
            $containerInstance->getDockerIdentifier(),
            $this->name,
        );
    }
}
