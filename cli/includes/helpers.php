<?php

namespace Valet;

use Exception;
use Illuminate\Container\Container;
use RuntimeException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\ConsoleOutput;

if (!isset($_SERVER['HOME'])) {
	$_SERVER['HOME'] = $_SERVER['USERPROFILE'];
}

$_SERVER['HOME'] = str_replace('\\', '/', $_SERVER['HOME']);

/*
 * Define the ~/.config/valet path as a constant.
 */
define('VALET_HOME_PATH', $_SERVER['HOME'] . '/.config/valet');
define('VALET_SERVER_PATH', str_replace('\\', '/', realpath(__DIR__ . '/../../server.php')));
define('VALET_STATIC_PREFIX', '41c270e4-5535-4daa-b23e-c269744c2f45');

define('VALET_LEGACY_HOME_PATH', $_SERVER['HOME'] . '/.valet');

/**
 * Output the given text to the console.
 *
 * @param  string  $output
 * @return void
 */
function info($output)
{
	output('<info>' . $output . '</info>');
}

/**
 * Debugging only.
 * Output the given array to the console using var_dump.
 *
 * @param  string  $output
 * @return void
 */
function info_dump($output)
{
	output('<info>' . var_dump($output) . '</info>');
}

/**
 * Output the given text to the console.
 *
 * @param  string  $output
 * @return void
 */
function warning($output)
{
	if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'testing') {
		throw new RuntimeException($output);
	}

	output('<fg=red>' . $output . '</>');
}

/**
 * Output errors to the console.
 *
 * @param  string  $output
 * @param  boolean $exception
 * @return void
 */
function error(string $output, $exception = false)
{
	if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'testing') {
		throw new RuntimeException($output);
	}
	if (!$exception) {
		(new ConsoleOutput)->getErrorOutput()->writeln("<error>$output</error>");
	}
	throw new \Exception($output);
}

if (!function_exists('array_is_list')) {
	/**
	 * Checks whether a given `array` is a list
	 * 
	 * *This function was introduced in PHP 8.1, so this is a polyfill only in usage on PHP versions below 8.1 (thanks to this StackOverflow answer: https://stackoverflow.com/a/173479/2358222).*
	 * 
	 * Determines if the given `array` is a list. An `array` is considered a list if its keys consist of consecutive numbers from `0` to `count($array)-1`.
	 * https://www.php.net/manual/function.array-is-list.php
	 * 
	 * @param $array The `array` being evaluated.
	 * 
	 * @return bool Returns `true` if `array` is a list, `false` otherwise.
	 */
	function array_is_list(array $array)
	{
		if ($array === []) {
			return true;
		}
		return array_keys($arr) === range(0, count($array) - 1);
	}
}

/**
 * Output a table to the console.
 *
 * @param  array  $headers
 * @param  array  $rows
 * @return void
 */
function table(array $headers = [], array $rows = [], $setHorizontal = false, $title = null)
{
	$table = new Table(new ConsoleOutput());

	// Symfony Console component from 6.1 added support for a vertical table.
	// But older versions won't reconise the function and will
	// spit out errors. So to avoid this, we need to check
	// whether the method exists and if it does, we can use it.
	if (method_exists(Table::class, 'setVertical') && !$setHorizontal) {
		$table->setVertical();
	}

	if ($title) {
		$table->setHeaderTitle($title);
	}
	if ($setHorizontal) {
		$rows = addTableSeparator($rows);
	}

	$table->setHeaders($headers)->setRows($rows);

	changeColumnMaxWidth($table, $headers, ["URL", "Path"], 30);

	$table->setStyle('box');

	$table->render();
}

/**
 * Defines the default table headers.
 *
 * @return array ['Site', 'Alias', 'Secured', 'PHP', 'URL', 'Alias URL', 'Path']
 */
function default_table_headers()
{
	return ['Site', 'Alias', 'Secured', 'PHP', 'URL', 'Alias URL', 'Path'];
}

/**
 * Change the max width of specified table columns.
 * 
 * @param Table $table The table instance
 * @param array $headers Table headers
 * @param array $columns The column names to change the width of
 * @param int $maxWidth The maximum width of the columns
 */
function changeColumnMaxWidth($table, $headers, $columns, $maxWidth)
{
	foreach ($columns as $column) {
		$index = array_search($column, $headers);
		// (column, width) - column is zero based.
		$table->setColumnMaxWidth($index, $maxWidth);
	}
}

/**
 * Add a table separator inbetween all the rows.
 * 
 * @param array $rows The array of rows
 */
function addTableSeparator($rows)
{
	$separatedRows;

	foreach ($rows as $key => $value) {
		// If the array is NOT a numerical indexed list, 
		// ie: it's an associative array...
		if (!array_is_list($rows)) {
			// Set the array element as it was originally.
			$separatedRows[$key] = $value;

			// If element is not the last, then add the table separator.
			if ($key !== array_key_last($rows)) {
				$separatedRows[] = new TableSeparator();
			}
		}
		// Otherwise, it's an indexed list...
		else {
			// If element is the first, then add it as it originally was.
			if ($key === array_key_first($rows)) {
				$separatedRows[$key] = $value;
			}
			// Otherwise, increment the index (so that that it doesn't
			// interfere with the separator).
			else {
				$separatedRows[$key + 1] = $value;
			}
			// If element is not the last, then add the table separator.
			if ($key !== array_key_last($rows)) {
				$separatedRows[$key + 1] = new TableSeparator();
			}
		}
	}

	return $separatedRows;
}

/**
 * Output the given text to the console.
 *
 * @param  string  $output
 * @return void
 */
function output($output)
{
	if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'testing') {
		return;
	}

	(new ConsoleOutput())->writeln($output);
}

if (!function_exists('resolve')) {
	/**
	 * Resolve the given class from the container.
	 *
	 * @param  string  $class
	 * @return mixed
	 */
	function resolve($class)
	{
		return Container::getInstance()->make($class);
	}
}

/**
 * Swap the given class implementation in the container.
 *
 * @param  string  $class
 * @param  mixed  $instance
 * @return void
 */
function swap($class, $instance)
{
	Container::getInstance()->instance($class, $instance);
}

if (!function_exists('retry')) {
	/**
	 * Retry the given function N times.
	 *
	 * @param  int  $retries
	 * @param  callable  $retries
	 * @param  int  $sleep
	 * @return mixed
	 */
	function retry($retries, $fn, $sleep = 0)
	{
		beginning:
		try {
			return $fn();
		} catch (Exception $e) {
			if (!$retries) {
				throw $e;
			}

			$retries--;

			if ($sleep > 0) {
				usleep($sleep * 1000);
			}

			goto beginning;
		}
	}
}

/**
 * @deprecated
 * Verify that the script is currently running as "sudo".
 *
 * @return void
 */
function should_be_sudo()
{
	if (!isset($_SERVER['SUDO_USER'])) {
		throw new Exception('This command must be run with sudo.');
	}
}

if (!function_exists('tap')) {
	/**
	 * Tap the given value.
	 *
	 * @param  mixed  $value
	 * @param  callable  $callback
	 * @return mixed
	 */
	function tap($value, callable $callback)
	{
		$callback($value);

		return $value;
	}
}

if (!function_exists('ends_with')) {
	/**
	 * Determine if a given string ends with a given substring.
	 *
	 * @param  string  $haystack
	 * @param  string|array  $needles
	 * @return bool
	 */
	function ends_with($haystack, $needles)
	{
		foreach ((array) $needles as $needle) {
			if (substr($haystack, -strlen($needle)) === (string) $needle) {
				return true;
			}
		}

		return false;
	}
}

if (!function_exists('starts_with')) {
	/**
	 * Determine if a given string starts with a given substring.
	 *
	 * @param  string  $haystack
	 * @param  string|string[]  $needles
	 * @return bool
	 */
	function starts_with($haystack, $needles)
	{
		foreach ((array) $needles as $needle) {
			if ((string) $needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0) {
				return true;
			}
		}

		return false;
	}
}

/**
 * Get the user.
 *
 * @return string
 */
function user()
{
	if (!isset($_SERVER['SUDO_USER'])) {
		if (!isset($_SERVER['USER'])) {
			return $_SERVER['USERNAME'];
		}

		return $_SERVER['USER'];
	}

	return $_SERVER['SUDO_USER'];
}