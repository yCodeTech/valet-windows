<?php

namespace Valet;

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

		symlink($target, $link);
	}

	/**
	 * Create a symlink to the given target for the non-root user.
	 *
	 * This uses the command line as PHP can't change symlink permissions.
	 *
	 * @param string $target
	 * @param string $link
	 * @return void
	 */
	public function symlinkAsUser($target, $link) {
		if ($this->exists($link)) {
			$this->unlink($link);
		}

		$mode = is_dir($target) ? 'J' : 'H';

		exec("mklink /{$mode} \"{$link}\" \"{$target}\"");
	}

	/**
	 * Delete the file at the given path.
	 *
	 * @param string $path
	 * @return void
	 */
	public function unlink($path) {
		if ($this->isLink($path)) {
			$dir = pathinfo($path, PATHINFO_DIRNAME);
			$link = pathinfo($path, PATHINFO_BASENAME);

			if (is_dir($path)) {
				exec("cd \"{$dir}\" && rmdir {$link}");
			}
			else {
				@unlink($path);
				@rmdir($path);
			}
		}
		elseif ($this->isDir($path)) {
			exec('cmd /C rmdir /s /q "' . $path . '"');
		}
		elseif (file_exists($path)) {
			@unlink($path);
			@rmdir($path);
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
		if (is_link($path)) {
			return true;
		}

		return $this->isDir($path) && filesize($path) === 0;
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
		return is_link($path) || @readlink($path) === false;
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