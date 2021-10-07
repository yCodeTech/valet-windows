<?php

namespace Valet;

use Symfony\Component\Process\Process;

class ProcessOutput
{
    /**
     * @var \Symfony\Component\Process\Process
     */
    public $process;

    /**
     * Create a new test process instance.
     *
     * @param  \Symfony\Component\Process\Process  $process
     * @return void
     */
    public function __construct(Process $process)
    {
        $this->process = $process;
    }

    /**
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->process->isSuccessful();
    }

    /**
     * @return string
     */
    public function getOutput(): string
    {
        if ($this->process->isSuccessful()) {
            return $this->process->getOutput();
        }

        return $this->process->getErrorOutput();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getOutput();
    }

    /**
     * Handle dynamic calls into macros or pass missing methods to the base process.
     *
     * @param  string  $method
     * @param  array  $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->process->{$method}(...$args);
    }
}
