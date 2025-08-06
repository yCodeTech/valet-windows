<?php

namespace Valet;

use CommandLine;

class Filesystem {
	/**
	 * Determine if the given path is a directory.
	 *
	 * @param string $path
	 * @return bool
	 */
	public function isDir($path) {
		return is_dir($path);
	}

	/**
	 * Create a directory.
	 *
	 * @param string $path
	 * @param string|null $owner
	 * @param int $mode
	 *
	 * @return void
	 */
	public function mkdir($path, $owner = null, $mode = 0755) {
		mkdir($path, $mode, true);

		if ($owner) {
			$this->chown($path, $owner);
		}
	}

	/**
	 * Create a directory as the non-root user.
	 *
	 * @param string $path
	 * @param int $mode
	 *
	 * @return void
	 */
	public function mkdirAsUser($path, $mode = 0755) {
		$this->mkdir($path, user(), $mode);
	}

	/**
	 * Ensure that the given directory exists.
	 *
	 * @param string $path
	 * @param string|null $owner
	 * @param int $mode
	 * @return void
	 */
	public function ensureDirExists($path, $owner = null, $mode = 0755) {
		if (!$this->isDir($path)) {
			$this->mkdir($path, $owner, $mode);
		}
	}

	/**
	 * Touch the given path.
	 *
	 * @param string $path
	 * @param string|null $owner
	 * @return string
	 */
	public function touch($path, $owner = null) {
		touch($path);

		if ($owner) {
			$this->chown($path, $owner);
		}

		return $path;
	}

	/**
	 * Touch the given path as the non-root user.
	 *
	 * @param string $path
	 * @return string
	 */
	public function touchAsUser($path) {
		return $this->touch($path, user());
	}

	/**
	 * Determine if the given file exists.
	 *
	 * @param string $path
	 * @return bool
	 */
	public function exists($path) {
		return file_exists($path);
	}

	/**
	 * Read the contents of the given file.
	 *
	 * @param string $path
	 * @return string
	 */
	public function get($path) {
		return file_get_contents($path);
	}

	/**
	 * Write to the given file.
	 *
	 * @param string $path
	 * @param string $contents
	 * @param string|null $owner
	 * @return void
	 */
	public function put($path, $contents, $owner = null) {
		file_put_contents($path, $contents);

		if ($owner) {
			$this->chown($path, $owner);
		}
	}

	/**
	 * Write to the given file as the non-root user.
	 *
	 * @param string $path
	 * @param string $contents
	 * @return void
	 */
	public function putAsUser($path, $contents) {
		$this->put($path, $contents, user());
	}

	/**
	 * Append the contents to the given file.
	 *
	 * @param string $path
	 * @param string $contents
	 * @param string|null $owner
	 * @return void
	 */
	public function append($path, $contents, $owner = null) {
		file_put_contents($path, $contents, FILE_APPEND);

		if ($owner) {
			$this->chown($path, $owner);
		}
	}

	/**
	 * Append the contents to the given file as the non-root user.
	 *
	 * @param string $path
	 * @param string $contents
	 * @return void
	 */
	public function appendAsUser($path, $contents) {
		$this->append($path, $contents, user());
	}

	/**
	 * Copy the given file to a new location.
	 *
	 * @param string $from
	 * @param string $to
	 * @return void
	 */
	public function copy($from, $to) {
		// The @ operator suppresses pre-error messages that occur in PHP internally.
		// We need to surpress the messages in order to properly handle them.
		@copy($from, $to) or error("Failed to copy", true);
	}

	/**
	 * Copy the given file to a new location for the non-root user.
	 *
	 * @param string $from
	 * @param string $to
	 * @return void
	 */
	public function copyAsUser($from, $to) {
		copy($from, $to);

		$this->chown($to, user());
	}

	/**
	 * Move file from one directory to another.
	 *
	 * This is a simple wrapper around the PHP rename function.
	 * By renaming the file path, we can move it to a new location. The filepath can be
	 * a file or a directory. If it's a directory, the contents of the directory will
	 * be moved at the same time. If the destination directory does not exist, it will be created.
	 *
	 * @param string $from
	 * @param string $to
	 */
	public function move($from, $to) {
		rename($from, $to);
	}

	/**
	 * Create a symlink to the given target.
	 *
	 * @param string $target
	 * @param string $link
	 * @return void
	 */
	public function symlink($target, $link) {
		if ($this->exists($link)) {
			$this->unlink($link);
		}

		// Use `mklink` with the `/D` param to create a directory symlink.
		// Use sudo to run with trusted installer privileges as the `/D` param requires it.
		CommandLine::sudo("mklink /D \"{$link}\" \"{$target}\"", true, true);
	}

	/**
	 * Delete the file at the given path.
	 *
	 * @param string $path
	 * @return void
	 */
	public function unlink($path) {
		// If the path is a symlinked directory OR is a file, remove it.
		if ($this->isLink($path) || $this->isFile($path)) {
			@unlink($path);
			@rmdir($path);
		}
		// If the path is a directory, remove it and all it's contents.
		elseif ($this->isDir($path)) {
			exec('cmd /C rmdir /s /q "' . $path . '"');
		}
	}

	/**
	 * Change the owner of the given path.
	 *
	 * @param string $path
	 * @param string $user
	 * @return void
	 */
	public function chown($path, $user) {
		chown($path, $user);
	}

	/**
	 * Change the group of the given path.
	 *
	 * @param string $path
	 * @param string $group
	 * @return void
	 */
	public function chgrp($path, $group) {
		chgrp($path, $group);
	}

	/**
	 * Resolve the given path.
	 *
	 * @param string $path
	 * @return string
	 */
	public function realpath($path, $normalise = true) {
		$realPath = realpath($path);
		return $normalise ? $this->normalisePath($realPath) : $realPath;
	}

	/**
	 * Normalise the given path by replacing backslashes with forward slashes.
	 *
	 * @param string $path
	 * @return string
	 */
	public function normalisePath($path) {
		return str_replace('\\', '/', $path);
	}

	/**
	 * Determine if the given path is a symbolic link.
	 *
	 * @param string $path
	 * @return bool
	 */
	public function isLink($path) {
		return is_link($path);
	}

	/**
	 * Resolve the given symbolic link.
	 *
	 * @param string $path
	 * @return string
	 */
	public function readLink($path) {
		return readlink($path);
	}

	/**
	 * Determine if the given path is a file.
	 *
	 * @param string $path
	 * @return bool
	 */
	public function isFile($path) {
		return is_file($path);
	}

	/**
	 * Remove all of the broken symbolic links at the given path.
	 *
	 * @param string $path
	 * @return void
	 */
	public function removeBrokenLinksAt($path) {
		collect($this->scandir($path))->filter(function ($file) use ($path) {
			return $this->isBrokenLink($path . '/' . $file);
		})->each(function ($file) use ($path) {
			$this->unlink($path . '/' . $file);
		});
	}

	/**
	 * Determine if the given path is a broken symbolic link.
	 *
	 * @param string $path
	 * @return bool
	 */
	public function isBrokenLink($path) {
		return $this->isLink($path) && !file_exists($this->readLink($path));
	}

	/**
	 * Convert all junction links in the given path to real symlinks.
	 *
	 * @param string $path The path to scan for junction links.
	 */
	public function convertJunctionsToSymlinks($path) {
		/**
		 * @var \Illuminate\Support\Collection
		 */
		$collection = $this->getJunctionLinks($path);

		// If there are no junction links to convert, we can exit early.
		if ($collection->isEmpty()) {
			return;
		}

		// Remove all the junction links and create new symlinks to the same path.
		$collection->each(function ($link) use ($path) {
			$output = CommandLine::run('cmd /C rmdir /s /q "' . $path . '/' . $link['linkName'] . '"');

			if ($output->isSuccessful()) {
				$this->symlink($link['path'], $link['linkName']);
			}
		});

		return;
	}

	/**
	 * Get all the junction links in the given path.
	 *
	 * @param string $path The path to scan for junction links.
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function getJunctionLinks($path) {
		/**
		 * @var \Illuminate\Support\Collection
		 */
		$collection = collect();

		$output = CommandLine::run("cmd /C dir \"$path\" /a:L | findstr \"<JUNCTION>\"");
		$outputArray = explode("\n", $output->getOutput());

		foreach ($outputArray as $line) {
			if (str_contains($line, '<JUNCTION>')) {
				// Split the line by whitespace, but ignore whitespace inside brackets.
				// This is to avoid splitting the path if it contains spaces.
				$line = preg_split('/[\s]+(?![^\[]*\])/', $line);

				// Set the link name and path to the collection.
				$collection->push([
					"linkName" => $line[3],
					"path" => str_replace(["[", "]"], "", $line[4])
				]);
			}
		}
		return $collection;
	}


	/**
	 * Scan the given directory path.
	 *
	 * @param string $path
	 * @return array
	 */
	public function scandir($path) {
		return collect(scandir($path))->reject(function ($file) {
			return in_array($file, ['.', '..']);
		})->values()->all();
	}


	/**
	 * Scan the given directory path recursively, returning an
	 * associative array of all files and directories.
	 *
	 * @param string $dir The directory to scan.
	 * @return array An associative array of all files and directories.
	 */
	public function scanDirRecursive($dir) {
		$result = [];
		$items = $this->scandir($dir);

		// Loop through each item in the directory.
		foreach ($items as $item) {
			// Skip the current and parent directory entries.
			if ($item === '.' || $item === '..') {
				continue;
			}
			// Get the full path of the item.
			$fullPath = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $item;

			// If the item is a directory and not a symlink...
			if ($this->isDir($fullPath) && !$this->isLink($fullPath)) {
				// Recursively scan the directory and add the directory name as the key,
				// and the result of the recursive scan as the value of the array.
				$result[$item] = $this->scanDirRecursive($fullPath);
			}
			// Otherwise, add the item to the result array.
			else {
				$result[] = $item;
			}
		}
		return $result;
	}

	/**
	 * Check if the given directory is empty.
	 *
	 * @param string $path The path to the directory to check.
	 * @return bool
	 */
	public function isDirEmpty($path) {
		return $this->isDir($path) && collect($this->scandir($path))->isEmpty();
	}

	/**
	 * Unzip the given zip file to the given path.
	 *
	 * @uses `tar` The Windows CMD `tar` command to zip/unzip files.
	 * The `-x` option extracts the zip file.
	 * The `-f` option specifies the zip file to extract.
	 * The `-C` option specifies the directory to extract to.
	 *
	 * @param string $zipFilePath
	 * @param string $extractToPath
	 */
	public function unzip($zipFilePath, $extractToPath) {
		$tar = getTarExecutable();
		CommandLine::run("$tar -xf $zipFilePath -C $extractToPath");
	}

	/**
	 * List the top-level directories in the given zip file.
	 *
	 * @uses `tar` The Windows CMD `tar` command to zip/unzip files and list files in the zip.
	 * The -t option lists the contents of the zip file.
	 * The -f option specifies the zip file to list.
	 *
	 * @param mixed $zipFilePath
	 * @return string[] Array of top-level directories in the zip file.
	 */
	public function listTopLevelZipDirs($zipFilePath) {
		$tar = getTarExecutable();
		// Get the contents of the zip file.
		$output = CommandLine::run("$tar -tf $zipFilePath");
		// Split the output into an array of lines.
		// Each line represents a file or directory in the zip file.
		$contents = explode("\n", trim($output->getOutput()));

		// Collect and map through each item in the contents and return the first part of
		// the path of each item.
		return collect($contents)->map(function ($item) {
				// Split the item by `/` and return the first part of the path with
				// the leading or trailing whitespace trimmed.
				// The first part of the path is the top-level directory in the zip file.
				return explode("/", trim($item))[0];
		})
			->unique()
			->values()
			->all();
	}

	/**
	 * Get stub file. If a custom stub file exists in the home path, use that instead.
	 *
	 * @param string $filename
	 *
	 * @return string
	 */
	public function getStub($filename) {
		$default = __DIR__ . '/../stubs/' . $filename;

		$custom = Valet::homePath() . "/stubs/$filename";

		$path = file_exists($custom) ? $custom : $default;

		return $this->get($path);
	}
}
