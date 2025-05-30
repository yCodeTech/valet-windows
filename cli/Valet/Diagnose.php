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
	protected $progressBar;


	/**
	 * Create a new Diagnose instance.
	 *
	 * @param CommandLine $cli
	 * @param Filesystem $files
	 * @return void
	 */
	public function __construct(CommandLine $cli, Filesystem $files) {
		$this->cli = $cli;
		$this->files = $files;

		$nginxPkgClass = resolve(Packages\Nginx::class);

		$this->commands = [
			'systeminfo',
			'valet --version',

			'Valet Home Structure placeholder',
			'Valet Bin Structure placeholder',

			'cat ' . \Configuration::path(),
			$nginxPkgClass->packageExe() . ' -v 2>&1',
			$nginxPkgClass->packageExe() . ' -c \"' . $nginxPkgClass->packagePath() . '/conf/nginx.conf\" -t -p ' . $nginxPkgClass->packagePath() . ' 2>&1',

			'foreach ($file in get-ChildItem -Path "' . $nginxPkgClass->packagePath() . '/conf/nginx.conf", "' . $nginxPkgClass->packagePath() . '/valet/valet.conf", "' . Valet::homePath() . '/Nginx/*.conf"){echo $file.fullname --------------------`n; Get-Content -Path $file; echo `n;}',

			valetBinPath() . 'ngrok.exe version',
			resolve(Packages\Gsudo::class)->packageExe() . ' -v',
			resolve(Packages\Ansicon::class)->packageExe() . ' /?',
			'cat "' . valetBinPath() . 'acrylic/Readme.txt"',
			'cat "' . valetBinPath() . 'winsw/README.md"',
			'php -v',
			'cmd /C "where /f php"',
			'php --ini',
			'php --info',
			'php --ri curl',
			'cmd /C curl --version',
			'cat "' . pathFilter(trim(\Valet::getComposerGlobalPath())) . '/composer.json"',
			'composer global diagnose --no-ansi 1>' . Valet::homePath() . '/composer.txt',
			'composer global outdated --format json'
		];
	}

	/**
	 * Run diagnostics.
	 *
	 * @param boolean $print Print the output as the commands are running.
	 * @param boolean $plainText Print and format the output as plain text (aka pretty print).
	 */
	public function run($print, $plainText) {
		$this->print = $print;

		$this->beforeRun();

		$results = collect($this->commands)->map(function ($command) {

			$this->beforeCommand($command);

			if ($this->isNonCliCommand($command)) {
				$output = $this->runNonCliCommand($command);
			}
			else {
				$output = $this->cli->powershell($command);
			}


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
	 * Run a non-CLI command, ie. run a task that is not a commandline command,
	 * and only achievable via PHP to generate the output.
	 *
	 * @param string $command The command placeholder to check for to run the task.
	 *
	 * @return string|array The output of the task.
	 */
	protected function runNonCliCommand($command) {

		/** Valet Home Structure & Valet Bin Structure **/
		if (str_contains($command, "Valet Home Structure") || str_contains($command, "Valet Bin Structure")) {
			/** Valet Home Structure **/
			if (str_contains($command, "Valet Home Structure")) {
				// Recursively scan all directories and files in valet's home path.
				$dirsArray = $this->files->scanDirRecursive(Valet::homePath());
			}
			/** Valet Bin Structure **/
			elseif (str_contains($command, "Valet Bin Structure")) {
				// Recursively scan all directories and files in valet's bin path.
				$dirsArray = $this->files->scanDirRecursive(valetBinPath());
			}

			// Generate a directory tree structure from the output array.

			$isBin = str_contains($command, "Bin");

			$parentDir = $isBin ? "valet/bin" : ".config/valet";
			$skip = $isBin ? ["temp"] : ['Log', 'Ngrok', "Xdebug"];

			$output = $this->generateDirectoryTree($dirsArray, $parentDir, $skip);
		}

		return $output;
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
			if ($this->isNonCliCommand($command)) {
				$command = str_replace("placeholder", "", $command);
			}
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
	 * Determines if the command is a non-CLI command,
	 * which is not a real command, but a placeholder for
	 * a command or task that is run via PHP instead of the CLI.
	 *
	 * @param string $command
	 * @return boolean
	 */
	protected function isNonCliCommand($command) {
		return strpos($command, 'placeholder') !== false;
	}

	/**
	 * Edit the output of a specific command, to improve
	 * the human readability of the diagnostics, and/or
	 * if the raw output isn't sufficient enough.
	 *
	 * @param string $command
	 * @param string|ProcessOutput $output
	 * @return string $output The edited output.
	 */
	protected function editOutput($command, $output) {
		/** System Info **/
		// Extract the OS Name and OS Version, lines 2 and 3.
		if (str_contains($command, "systeminfo")) {
			$output = explode("\n", $output);
			$output = implode("\n", [$output[2], $output[3]]);
		}

		/** Nginx **/
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

		/** Composer Diagnose **/
		if (str_contains($command, "composer global diagnose")) {
			$output = $this->cli->powershell('cat '. Valet::homePath() .'/composer.txt');
			$this->files->unlink(Valet::homePath() .'/composer.txt');
		}

		/** Composer Outdated **/
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

		/** Acrylic **/
		if (str_contains($command, "acrylic")) {
			if (preg_match("/version is:\s+\d+(\.\d+)+/", $output, $matches)) {
				$output = "Acrylic " . preg_replace("/:\s+/", " ", $matches[0]);
			}
		}

		/** WinSW **/
		if (str_contains($command, "winsw")) {
			if (preg_match("/\(v\d+(\.\d+)+\)/", $output, $matches)) {
				$output = "WinSW version is " . preg_replace("/(\(|\))/", "", $matches[0]);
			}
		}

		return $output;
	}

	/**
	 * Generate a directory tree structure from the output array.
	 * This is used to visually represent the directory structures.
	 *
	 * @param array $array The array to generate the tree from.
	 * @param string $parentDir The parent directory name to start the tree from.
	 * @param array $skip An array of directory names to skip looping their files.
	 * @param string $indent The indentation string to use for the tree structure.
	 * This is very important as it determines the visual representation of the tree.
	 * It uses combinations of spaces and tree characters to create the structure.
	 *
	 * @return string The generated directory tree structure.
	 */
	private function generateDirectoryTree($array, $parentDir, $skip, $indent = '') {
		// Setup the tree characters
		$treeItemChar = "┣━";
		$treeSeparatorChar = "┃";
		$treeEndItemChar = "┗━";
		$dirEmoji = "📁";

		// Helper to check if any skip string is in the dir name
		$shouldSkip = function ($name) use ($skip) {
			foreach ($skip as $s) {
				if (stripos($name, $s) !== false) {
					return true;
				}
			}
			return false;
		};

		// If the indent is empty, then this is the first line of the tree,
		// so start the tree with the parent directory name, otherwise an empty string.
		$tree = $indent === '' ? "$parentDir\n$treeSeparatorChar\n" : '';

		$entries = [];
		foreach ($array as $key => $value) {
			$name = is_string($key) ? $key : $value;
			$entries[] = ['name' => $name, 'value' => $value];
		}
		$count = count($entries);

		foreach ($entries as $i => $entry) {
			$name = $entry['name'];
			$value = $entry['value'];
			$isLast = ($i === $count - 1);
			$isDir = is_array($value);
			$isNextItemDir = ($i < $count - 1 && is_array($entries[$i + 1]['value']));

			// If the item is a directory, then add the directory emoji to the item character.
			// Otherwise, just use the item character.
			// This is so that we can better visually determine which items are directories.
			$itemChar = $isDir ? $treeItemChar . $dirEmoji : $treeItemChar;
			$endItemChar = $isDir ? $treeEndItemChar . $dirEmoji : $treeEndItemChar;

			// If the item is a directory and should be skipped, then just output the directory name
			if ($shouldSkip($name)) {
				// Add the directory name to the tree.
				$tree .= $indent . ($isLast ? $endItemChar : $itemChar) . " $name\n";
				// Add a line separation.
				$tree .= $indent . "$treeSeparatorChar\n";
				continue;
			}

			// If the item is not a directory, ie a file, then add the file name to the tree.
			$tree .= $indent . ($isLast ? $endItemChar : $itemChar) . " $name\n";

			if (!$isDir && !$isLast && $isNextItemDir) {
				// Add a line separation between the file and the next item.
				$tree .= $indent . "$treeSeparatorChar\n";
			}

			// If the item is a directory...
			if ($isDir) {
				// Recurse into subdirectory and increase the indentation.
				$tree .= $this->generateDirectoryTree(
					$value,
					$name,
					$skip,
					$indent . ($isLast ? "      " : "$treeSeparatorChar     ")
				);
			}

			// Get the current line in the string (the last line added to $tree)
			$lines = explode("\n", rtrim($tree, "\n"));
			$currentLine = trim(end($lines));

			// If item is a directory AND the next item is also a directory,
			// OR
			// the current line contains a tree end item character AND
			// the next item is not a directory (ie. is a file) AND
			// is also not last,
			// then add a line separation.
			//
			// This separates consecutive empty directories
			// and also separates a directory's tree from the next file.
			if (($isDir && $isNextItemDir) || (str_contains($currentLine, $treeEndItemChar) && !$isNextItemDir && !$isLast)) {
				// Add a line separation.
				$tree .= $indent . "$treeSeparatorChar\n";
			}
		}

		return $tree;
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
			'<details>%s<summary>%s</summary>%s<p>%s</p>%s<pre><code>%s</code></pre>%s</details>',
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

		$file = Valet::homePath() . '/valet_diagnostics.txt';

		// Write to file as UTF-8 with BOM to help Set-Clipboard recognize Unicode characters.
		$this->files->put($file, "\xEF\xBB\xBF" . $output);
		$this->cli->powershell('type ' . $file . ' | Set-Clipboard');
		$this->files->unlink($file);
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
			"Valet Home Structure",
			"Valet Bin Structure",
			"Valet Config",
			"nginx Version",
			"nginx Config Check",
			"nginx Config Files",
			"ngrok Version",
			"gsudo Version",
			"Ansicon Version",
			"Acrylic Version",
			"WinSW Version",
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
