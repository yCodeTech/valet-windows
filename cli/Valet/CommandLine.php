<?php

namespace Valet;

use Valet\Packages\Gsudo;

use Symfony\Component\Process\Process;

class CommandLine {
	/**
	 * Pass the command to the command line and display the output.
	 *
	 * @param string $command
	 * @return void
	 */
	public function passthru($command) {
		passthru($command);
	}

	/**
	 * Execute command via shell and return the complete output as a string
	 *
	 * @param string $command
	 * @return bool|string|null The output.
	 */
	public function shellExec($command) {
		return shell_exec($command);
	}

	/**
	 * Pass the given Valet command to the command line with elevated privileges using gsudo.
	 *
	 * gsudo is a sudo equivalent of the Mac `sudo` utility. It allows the user to run commands as the root user with elevated privileges with minimal amount of UAC popups, ie. only 1 UAC popup.
	 *
	 * https://github.com/gerardog/gsudo
	 *
	 * @param string $valetCommand The Valet command to run.
	 * @param bool $asTrustedInstaller Set to `true` to run the command as a trusted installer. Default: `false`
	 * @param bool $quiet Set to `true` to suppress the output. Default: `false`
	 */
	public function sudo($valetCommand, $asTrustedInstaller = false, $quiet = false) {
		$gsudoClass = resolve(Gsudo::class);

		$gsudo = $asTrustedInstaller ? $gsudoClass->runAsTrustedInstaller() : $gsudoClass->runAsSystem();

		$this->passthru($gsudo . ' ' . $valetCommand . ($quiet ? ' > nul 2>&1' : ''));
	}

	/**
	 * Run the given command.
	 *
	 * Uses the Symfony Process component to run the command.
	 *
	 * @param string $command
	 * @param callable|null $onError
	 * @param bool $realTimeOutput Set to `true` to get the output in real time as the command is running. Default: `false`
	 * @return ProcessOutput
	 */
	public function run($command, ?callable $onError = null, $realTimeOutput = false) {
		return $this->runCommand($command, $onError, $realTimeOutput);
	}

	/**
	 * Run the given command with PowerShell.
	 *
	 * @param string $command
	 * @param callable|null $onError
	 * @param bool $realTimeOutput Set to `true` to get the output in real time as the command is running. Default: `false`
	 * @return ProcessOutput
	 */
	public function powershell(string $command, ?callable $onError = null, $realTimeOutput = false) {
		return $this->runCommand("powershell -command \"$command\"", $onError, $realTimeOutput);
	}

	/**
	 * Run the given command and exit if fails.
	 *
	 * @param string $command
	 * @param callable|null $onError
	 * @param bool $realTimeOutput Set to `true` to get the output in real time as the command is running. Default: `false`
	 * @return ProcessOutput
	 */
	public function runOrExit($command, ?callable $onError = null, $realTimeOutput = false) {
		/**
		 * @param int $code Error code
		 * @param string $output Error output
		 */
		return $this->run($command, function ($code, $output) use ($onError) {
			if ($onError) {
				$onError($code, $output);
			}

			exit(1);
		}, $realTimeOutput);
	}

	/**
	 * Run the given command.
	 *
	 * @param string $command
	 * @param callable|null $onError
	 * @param bool $realTimeOutput Set to `true` to get the output in real time as the command is running. Default: `false`
	 * @return ProcessOutput|void Returns a ProcessOutput only if the real time is `false`, otherwise it doesn't return anything (void) as it's echoing out in real time.
	 */
	public function runCommand($command, ?callable $onError = null, $realTimeOutput = false) {
		$onError = $onError ?: function () {
		};

		// Symfony's 4.x Process component has deprecated passing a command string
		// to the constructor, but older versions (which Valet's Composer
		// constraints allow) don't have the fromShellCommandLine method.
		// For more information, see: https://github.com/laravel/valet/pull/761
		if (method_exists(Process::class, 'fromShellCommandline')) {
			$process = Process::fromShellCommandline($command);
		}
		else {
			$process = new Process($command);
		}

		/**
		 * Output in real time
		 */
		if ($realTimeOutput) {
			// Use setTimeout of 0 to allow it to run seemingly forever, until user cancels it.
			$process->setTimeout(0)->run(function ($type, $line) {
				if (Process::ERR === $type) {
					echo 'ERROR: ' . $line;
				}
				else {
					echo $line;
				}
			});
		}
		else {
			$processOutput = '';

			$process->setTimeout(60)->run(function ($type, $line) use (&$processOutput) {
				$processOutput .= $line;
			});
		}

		if ($process->getExitCode() !== 0) {
			$onError($process->getExitCode(), $processOutput);
		}

		if (!$realTimeOutput) {
			return new ProcessOutput($process);
		}
	}
}
