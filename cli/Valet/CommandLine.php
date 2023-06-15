<?php

namespace Valet;

use Symfony\Component\Process\Process;

class CommandLine
{
	/**
	 * Pass the command to the command line and display the output.
	 *
	 * @param  string  $command
	 * @return void
	 */
	public function passthru($command)
	{
		passthru($command);
	}

	/**
	 * Pass the given valet command to the command line with elevated privileges using gsudo.
	 * 
	 * gsudo is a sudo equivalent of the Mac `sudo` utility. It allows the user to run commands as the root user with elevated privileges with minimal amount of UAC popups, ie. only 1 UAC popup.
	 * 
	 * https://github.com/gerardog/gsudo
	 * 
	 * @param string $valetCommand The valet command to run.
	 */
	public function sudo($valetCommand)
	{
		$gsudo = realpath(valetBinPath() . 'gsudo/gsudo.exe') . " --system -d ";
		$this->passthru($gsudo . " $valetCommand");
	}

	/**
	 * Run the given command as the non-root user.
	 *
	 * @param  string  $command
	 * @param  callable  $onError
	 * @return ProcessOutput
	 */
	public function run($command, callable $onError = null)
	{
		return $this->runCommand($command, $onError);
	}

	/**
	 * Run the given command.
	 *
	 * @param  string  $command
	 * @param  callable  $onError
	 * @return ProcessOutput
	 */
	public function runAsUser($command, callable $onError = null)
	{
		return $this->runCommand($command, $onError);
	}

	/**
	 * Run the given command with PowerShell.
	 *
	 * @param  string  $command
	 * @param  callable|null  $onError
	 * @return ProcessOutput
	 */
	public function powershell(string $command, callable $onError = null)
	{
		return $this->runCommand("powershell -command \"$command\"", $onError);
	}

	/**
	 * Run the given command and exit if fails.
	 *
	 * @param  string  $command
	 * @param  callable  $onError  (int $code, string $output)
	 * @return ProcessOutput
	 */
	public function runOrExit($command, callable $onError = null)
	{
		return $this->run($command, function ($code, $output) use ($onError) {
			if ($onError) {
				$onError($code, $output);
			}

			exit(1);
		});
	}

	/**
	 * Run the given command.
	 *
	 * @param  string  $command
	 * @param  callable  $onError
	 * @return ProcessOutput
	 */
	public function runCommand($command, callable $onError = null)
	{
		$onError = $onError ?: function () {
		};

		// Symfony's 4.x Process component has deprecated passing a command string
		// to the constructor, but older versions (which Valet's Composer
		// constraints allow) don't have the fromShellCommandLine method.
		// For more information, see: https://github.com/laravel/valet/pull/761
		if (method_exists(Process::class, 'fromShellCommandline')) {
			$process = Process::fromShellCommandline($command);
		} else {
			$process = new Process($command);
		}

		$processOutput = '';
		$process->setTimeout(60)->run(function ($type, $line) use (&$processOutput) {
			$processOutput .= $line;
		});

		if ($process->getExitCode() !== 0) {
			$onError($process->getExitCode(), $processOutput);
		}

		return new ProcessOutput($process);
	}
}