<?php

namespace Tests\Support;

use Symfony\Component\Process\Process;
use Valet\ProcessOutput;

class FakeProcessOutput extends ProcessOutput
{
    /**
     * @var string
     */
    public $output = '';

    /**
     * @var bool
     */
    public $successfull = true;

    /**
     * @param  string  $output
     * @return self
     */
    public static function successfull(string $output = ''): self
    {
        $process = new FakeProcessOutput(new Process([]));
        $process->output = $output;
        $process->successfull = true;

        return $process;
    }

    /**
     * @param  string  $output
     * @return self
     */
    public static function unsuccessfull(string $output = ''): self
    {
        $process = new FakeProcessOutput(new Process([]));
        $process->output = $output;
        $process->successfull = false;

        return $process;
    }

    /**
     * @return string
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    /**
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->successfull;
    }
}
