<?php

namespace Valet;

class Diagnose {
	/**
	 * The commands to run.
	 *
	 * @var array
	 */
	protected $commands;

	protected $cli;
	protected $files;
	protected $print;

	/**
	 * Create a new Diagnose instance.
	 *
	 * @param  CommandLine  $cli
	 * @param  Filesystem  $files
	 * @return void
	 */
	public function __construct(CommandLine $cli, Filesystem $files) {
		$this->cli = $cli;
		$this->files = $files;
		$this->commands = [
			'systeminfo',
			'valet --version',
			'cat ~/.config/valet/config.json',
			valetBinPath() . 'nginx/nginx.exe -v 2>&1',
			valetBinPath() . 'nginx/nginx.exe -c \"' . __DIR__ . '/../../bin/nginx/conf/nginx.conf\" -t -p ' . valetBinPath() . 'nginx 2>&1',
			'foreach ($file in get-ChildItem -Path "' . valetBinPath() . 'nginx/conf/nginx.conf", "' . valetBinPath() . 'nginx/valet/valet.conf", "' . VALET_HOME_PATH . '/Nginx/*.conf"){echo $file.fullname --------------------`n; Get-Content -Path $file; echo `n;}',
			valetBinPath() . 'ngrok.exe version',
			'php -v',
			'cmd /C "where /f php"',
			'php --ini',
			'php --info',
			'php --ri curl',
			'cmd /C curl --version',
			'cat "' . pathFilter(COMPOSER_GLOBAL_PATH) . '/composer.json"',
			'composer global diagnose --no-ansi 1>' . VALET_HOME_PATH . '/composer.txt',
			'composer global outdated --format json'
		];
	}

	/**
	 * Run diagnostics.
	 *
	 * @param boolean $print Print the output as the commands are running.
	 * @param boolean $plain Print and format the output as plain text (aka pretty print).
	 */
	public function run($print, $plainText) {
		$this->print = $print;

		$this->beforeRun();

		$results = collect($this->commands)->map(function ($command) {

			$this->beforeCommand($command);

			$output = $this->cli->powershell($command);

			if ($this->ignoreOutput($command)) {
				return;
			}

			$output = $this->editOutput($command, $output);

			$this->afterCommand($command, $output);

			return compact('command', 'output');

		})->filter()->values();

		$output = $this->format($results, $plainText);

		if ($plainText) {
			$formatted_for_copy = $output[1];
			$output = $output[0];
		}


		if (!$this->print) {
			output(PHP_EOL . PHP_EOL . $output);
		}

		$this->copyToClipboard($plainText ? $formatted_for_copy : $output);

		$this->afterRun();
	}

	/**
	 * Before running the diagnostics.
	 */
	protected function beforeRun() {
		if ($this->print) {
			return;
		}

		// Replace only single backslashes (\) within paths to forward slashes (/), ie. does not replace the backslashes
		// before a double quote. So the double quote is still escaped.
		// e.g. \"\..\path\" --> \"/../path\"
		$this->commands = preg_replace('/\\\(?![\\"])/', "/", $this->commands);

		$this->progressBar = progressbar(count($this->commands), "Diagnosing", "Valet");
	}

	/**
	 * After running the diagnostics.
	 */
	protected function afterRun() {
		if (!$this->print && $this->progressBar) {
			$this->progressBar->finish();
		}

		output('');
	}

	/**
	 * Before each command.
	 *
	 * @param string $command
	 */
	protected function beforeCommand($command) {
		if ($this->print) {
			info(PHP_EOL . "$ $command");
		}
	}

	/**
	 * After each command.
	 *
	 * @param string $command
	 * @param string $output
	 */
	protected function afterCommand($command, $output) {
		if ($this->print) {
			output(trim($output));
		}
		else {
			$this->progressBar->advance();
		}
	}

	/**
	 * Determines if Valet should ignore the output of a command.
	 *
	 * @param string $command
	 * @return boolean
	 */
	protected function ignoreOutput($command) {
		return strpos($command, '> /dev/null 2>&1') !== false;
	}

	/**
	 * Edit the output of a specific command, to improve
	 * the human readability of the diagnostics, and/or
	 * if the raw output isn't sufficient enough.
	 *
	 * @param string $command
	 * @param string $output
	 * @return string $output The edited output.
	 */
	protected function editOutput($command, $output) {
		// Extract the OS Name and OS Version, lines 2 and 3.
		if (str_contains($command, "systeminfo")) {
			$output = explode("\n", $output);
			$output = implode("\n", [$output[2], $output[3]]);
		}

		if (str_contains($command, "nginx.exe")) {
			$output = $output->__toString();

			if (str_contains($output, "nginx version")) {
				$version = explode("version:", $output);
				$version = preg_split("/[\s,]+/", $version[1]);

				$output = "nginx version: {$version[1]}";
			}
			elseif (str_contains($output, "syntax is ok") && str_contains($output, "successful")) {
				$configFile = explode("file", $output, 2)[1];
				$configFile = trim(explode(".conf", $configFile)[0] . ".conf");

				$output = "The syntax is ok in the configuration file:\n{$configFile}\n<fg=green>The test is successful</>";
			}
		}

		if (str_contains($command, "composer global diagnose")) {
			$output = $this->cli->powershell('cat '. VALET_HOME_PATH .'/composer.txt');
			$this->files->unlink(VALET_HOME_PATH .'/composer.txt');
		}

		if (str_contains($command, "composer global outdated")) {
			$output = json_decode($output, true);
			$output = $output["installed"];

			foreach ($output as $key => $item) {
				if (!$item["homepage"]) {
					unset($output[$key]["homepage"]);
				}

				if ($item["latest-status"] === "semver-safe-update") {
					$output[$key]["latest-status"] = "A patch or minor release available. Update is recommended.";
				}
				elseif ($item["latest-status"] === "update-possible") {
					$output[$key]["latest-status"] = "A major release available. Update is possible.";
				}
				else {
					$output[$key]["latest-status"] = "Up to date.";
				}

				$output[$key]["direct-dependency"] = var_export($item["direct-dependency"], true);
				$output[$key]["abandoned"] = var_export($item["abandoned"], true);
			}

			$output = json_encode($output, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		}

		return $output;
	}

	/**
	 * Format the output for the terminal.
	 *
	 * @param \Illuminate\Support\Collection $results A collection of the outputs.
	 * @param boolean $plainText
	 * @return array|string The formatted output as a `string`,
	 * or if `plainText` is `true`, then an `array` of the formatted plain output
	 * as a `string` for the terminal
	 * AND a formatted HTML output as a `string` to copy to the clipboard.
	 */
	protected function format($results, $plainText) {
		$results = $this->combineWithHeadings($results);

		$formatted_for_copy = collect();

		$formatted = $results->map(function ($result, $key) use ($plainText, $formatted_for_copy) {
			$command = $result['command'];
			$command = "$ {$command}";
			$heading_underline = str_repeat('=', strlen($key));
			$heading = PHP_EOL . $key . PHP_EOL . $heading_underline . PHP_EOL;
			$output = trim($result['output']);

			if ($plainText) {
				// Push the format for copy output into the separate collection.
				$formatted_for_copy->push($this->formatForCopy($command, $output, str_replace($heading_underline, "", $heading)));

				if (str_contains($command, "composer global outdated")) {
					$output = json_decode($output, true);

					foreach ($output as $key => $item) {
						$output[$key]["name"] = "Name: " . $item["name"];
						$output[$key]["direct-dependency"] = "Direct Dependency: " . $item["direct-dependency"];

						if (isset($item["homepage"])) {
							$output[$key]["homepage"] = "Homepage: " . $item["homepage"];
						}

						$output[$key]["source"] = "Source: " . $item["source"];
						$output[$key]["version"] = "Installed Version: " . $item["version"];
						$output[$key]["latest"] = "Latest: " . $item["latest"];
						$output[$key]["latest-status"] = "Status: " . $item["latest-status"];
						$output[$key]["description"] = "Description: " . $item["description"];
						$output[$key]["abandoned"] = "Abandoned: " . $item["abandoned"];
					}


					$output = implode("\n\n\n----\n\n\n", array_map(function ($a) {
						return implode("\n\n", $a);
					}, $output));
				}

				// Join the command and heading together.
				$output = implode(PHP_EOL, ["<info>$command</info>" . PHP_EOL, $output]);
				// Join the heading and the new output together.
				$output = implode(PHP_EOL, [$heading, $output]);

				return $output;
			}

			return $this->formatForCopy($command, $output, str_replace($heading_underline, "", $heading));
		});

		if ($plainText) {
			return [
				$formatted->implode(PHP_EOL . PHP_EOL . str_repeat('-', 50) . PHP_EOL),
				$formatted_for_copy->implode(PHP_EOL . PHP_EOL)
			];
		}
		else {
			return $formatted->implode($plainText ? PHP_EOL . PHP_EOL . str_repeat('-', 50) . PHP_EOL : PHP_EOL);
		}
	}

	/**
	 * Format the output as HTML to copy to the clipboard,
	 * usually for reporting issues on Github.
	 *
	 * @param string $command
	 * @param string $output
	 * @param string $heading The heading to output before the command name.
	 */
	protected function formatForCopy($command, $output, $heading) {
		if (str_contains($command, "composer global outdated")) {
			$table = sprintf(
				'<tr>%s<th>%s</th>%s<th>%s</th>%s<th>%s</th>%s<th>%s</th>%s<th>%s</th>%s<th>%s</th>%s</tr>',
				PHP_EOL,
				"Name",
				PHP_EOL,
				"Installed Version",
				PHP_EOL,
				"Latest",
				PHP_EOL,
				"Status",
				PHP_EOL,
				"Description",
				PHP_EOL,
				"Abandoned",
				PHP_EOL
			);

			foreach (json_decode($output, true) as $key => $item) {
				$table .= sprintf(
					'<tr>%s<td><a href="%s">%s</a></td>%s<td>%s</td>%s<td>%s</td>%s<td>%s</td>%s<td>%s</td>%s<td>%s</td>%s</tr>',
					PHP_EOL,
					$item['source'],
					$item["name"],
					PHP_EOL,
					$item["version"],
					PHP_EOL,
					$item["latest"],
					PHP_EOL,
					$item["latest-status"],
					PHP_EOL,
					$item["description"],
					PHP_EOL,
					$item["abandoned"],
					PHP_EOL
				);
			}

			return sprintf(
				'<details>%s<summary>%s</summary>%s<p>%s</p>%s<table>%s</table>%s</details>',
				PHP_EOL,
				$heading,
				PHP_EOL,
				$command,
				PHP_EOL,
				$table,
				PHP_EOL
			);
		}

		return sprintf(
			'<details>%s<summary>%s</summary>%s<p>%s</p>%s<pre>%s</pre>%s</details>',
			PHP_EOL,
			$heading,
			PHP_EOL,
			$command,
			PHP_EOL,
			$output,
			PHP_EOL
		);
	}

	/**
	 * Copy output to the clipboard.
	 *
	 * @param string $output The formatted output
	 */
	protected function copyToClipboard($output) {
		// Remove the crazy ANSI escape characters like:
		// [32m
		// ]8;;
		// [39m
		// 
		// that are leftover from the colourings and makes a complete mess
		// of the output to clipboard.
		// Code based on https://stackoverflow.com/a/40731340/2358222
		$output = preg_replace('/(\e)|([[]|[]])[A-Za-z0-9];*[0-9]*m?/', '', $output);

		$this->files->put(VALET_HOME_PATH . '/valet_diagnostics.txt', $output);
		$this->cli->powershell('type ' . VALET_HOME_PATH . '/valet_diagnostics.txt | clip');
		$this->files->unlink(VALET_HOME_PATH . '/valet_diagnostics.txt');
	}

	/**
	 * Combine the defined command headings with the output results,
	 * creating a new collection with the headings as the keys.
	 *
	 * @param \Illuminate\Support\Collection $results A collection of the outputs.
	 * @return \Illuminate\Support\Collection The new combined collection
	 */
	protected function combineWithHeadings($results) {
		return collect([
			"System Version",
			"Valet Version",
			"Valet Config",
			"nginx Version",
			"nginx Config Check",
			"nginx Config Files",
			"ngrok Version",
			"PHP Version",
			"PHP Location",
			"PHP Ini Location",
			"PHP Information",
			"PHP cURL Information",
			"Windows libcurl Version",
			"composer.json Global File",
			"Composer Diagnostics",
			"Outdated Composer Packages"
		])->combine($results);
	}
}
