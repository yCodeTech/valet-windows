<?php

namespace Valet;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class Diagnose
{
    /**
     * @var array
     */
    protected $commands = [
        'systeminfo | findstr /B /C:"OS Name" /C:"OS Version"',
        'valet --version',
        'cat ~/.config/valet/config.json',
        // 'cat ~/.composer/composer.json',
        'composer global diagnose',
        'composer global outdated',
        // 'ls -al /etc/sudoers.d/',
        // 'brew update > /dev/null 2>&1',
        // 'brew config',
        // 'brew services list',
        // 'brew list --formula --versions | grep -E "(php|nginx|dnsmasq|mariadb|mysql|mailhog|openssl)(@\d\..*)?\s"',
        // 'brew outdated',
        // 'brew tap',
        'php -v',
        'which -a php',
        'php --ini',
        // __DIR__.'/../../bin/nginx/nginx.exe -v',
        // 'curl --version',
        'php --ri curl',
        // __DIR__.'/../../bin/ngrok.exe version',
        'ls -al ~/.ngrok2',
        // 'brew info nginx',
        // 'brew info php',
        // 'brew info openssl',
        // 'openssl version -a',
        // 'openssl ciphers',
        // 'sudo nginx -t',
        // 'which -a php-fpm',
        // BREW_PREFIX.'/opt/php/sbin/php-fpm -v',
        // 'sudo '.BREW_PREFIX.'/opt/php/sbin/php-fpm -y '.PHP_SYSCONFDIR.'/php-fpm.conf --test',
        // 'ls -al ~/Library/LaunchAgents | grep homebrew',
        // 'ls -al /Library/LaunchAgents | grep homebrew',
        // 'ls -al /Library/LaunchDaemons | grep homebrew',
        // 'ls -aln /etc/resolv.conf',
        // 'cat /etc/resolv.conf',
    ];

    protected $cli;
    protected $files;
    protected $print;
    protected $progressBar;

    /**
     * Create a new Diagnose instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @return void
     */
    public function __construct(CommandLine $cli, Filesystem $files)
    {
        $this->cli = $cli;
        $this->files = $files;
    }

    /**
     * Run diagnostics.
     */
    public function run($print, $plainText)
    {
        $this->print = $print;

        $this->beforeRun();

        $results = collect($this->commands)->map(function ($command) {
            $this->beforeCommand($command);

            $output = $this->runCommand($command);

            if ($this->ignoreOutput($command)) {
                return;
            }

            $this->afterCommand($command, $output);

            return compact('command', 'output');
        })->filter()->values();

        $output = $this->format($results, $plainText);

        if (! $this->print) {
            output(PHP_EOL.PHP_EOL.$output);
        }

        // $this->files->put('valet_diagnostics.txt', $output);
        // $this->cli->run('type valet_diagnostics.txt | clip');
        // $this->files->unlink('valet_diagnostics.txt');

        $this->afterRun();
    }

    protected function beforeRun()
    {
        if ($this->print) {
            return;
        }

        $this->progressBar = new ProgressBar(new ConsoleOutput, count($this->commands));

        $this->progressBar->start();
    }

    protected function afterRun()
    {
        if ($this->progressBar) {
            $this->progressBar->finish();
        }

        output('');
    }

    protected function runCommand($command)
    {
        return strpos($command, 'sudo ') === 0
            ? $this->cli->run($command)
            : $this->cli->runAsUser($command);
    }

    protected function beforeCommand($command)
    {
        if ($this->print) {
            info(PHP_EOL."$ $command");
        }
    }

    protected function afterCommand($command, $output)
    {
        if ($this->print) {
            output(trim($output));
        } else {
            $this->progressBar->advance();
        }
    }

    protected function ignoreOutput($command)
    {
        return strpos($command, '> /dev/null 2>&1') !== false;
    }

    protected function format($results, $plainText)
    {
        return $results->map(function ($result) use ($plainText) {
            $command = $result['command'];
            $output = trim($result['output']);

            if ($plainText) {
                return implode(PHP_EOL, ["$ {$command}", $output]);
            }

            return sprintf(
                '<details>%s<summary>%s</summary>%s<pre>%s</pre>%s</details>',
                PHP_EOL, $command, PHP_EOL, $output, PHP_EOL
            );
        })->implode($plainText ? PHP_EOL.str_repeat('-', 20).PHP_EOL : PHP_EOL);
    }
}
