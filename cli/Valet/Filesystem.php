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
	 * @return void
	 */
	public function mkdir($path, $owner = null, $mode = 0755) {
		mkdir($path, $mode, true);

		if ($owner) {
			$this->chown($path, $owner);
		}
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
	 * Create a directory as the non-root user.
	 *
	 * @param string $path
	 * @param int $mode
	 * @return void
	 */
	public function mkdirAsUser($path, $mode = 0755) {
		$this->mkdir($path, user(), $mode);
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
	public function realpath($path) {
		return realpath($path);
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

		if ($collection->isEmpty()) {
			return;
		}
		// Remove all the junction links and create new symlinks to the same path.
		$collection->each(function ($link) use ($path) {
			$output = CommandLine::run('cmd /C rmdir /s /q "' . $path . '/' . $link['linkName']. '"');

			if ($output->isSuccessful()) {
				$this->symlink($link['path'], $link['linkName']);
			}
		});
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
}