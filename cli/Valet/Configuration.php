<?php

namespace Valet;

use Illuminate\Support\Arr;

class Configuration {
	/**
	 * @var Filesystem
	 */
	protected $files;

	/**
	 * Create a new Valet configuration class instance.
	 *
	 * @param Filesystem $filesystem
	 * @return void
	 */
	public function __construct(Filesystem $files) {
		$this->files = $files;
	}

	/**
	 * Install the Valet configuration file.
	 *
	 * @return void
	 */
	public function install() {
		$this->createConfigurationDirectory();
		$this->createDriversDirectory();
		$this->createSitesDirectory();
		$this->createExtensionsDirectory();
		$this->createLogDirectory();
		$this->createCertificatesDirectory();
		$this->createServicesDirectory();
		$this->writeBaseConfiguration();

		// Copy the emergency stop and uninstall services script to the Valet home
		// directory for safe keeping.
		$this->files->copy(
			realpath(__DIR__ . '/../../emergency_uninstall_services.bat'),
			$this->valetHomePath("emergency_uninstall_services.bat")
		);

		$this->files->chown($this->path(), user());
	}

	/**
	 * Create the Valet configuration directory.
	 *
	 * @return void
	 */
	public function createConfigurationDirectory() {
		// The preg_replace gets "C:/Users/Username/.config"
		$this->files->ensureDirExists(preg_replace('~/valet$~', '', $this->valetHomePath()), user());
		$this->files->ensureDirExists($this->valetHomePath(), user());
	}

	/**
	 * Create the Valet drivers directory.
	 *
	 * @return void
	 */
	public function createDriversDirectory() {
		$driversPath = $this->valetHomePath('Drivers');

		if ($this->files->isDir($driversPath)) {
			return;
		}

		$this->files->mkdirAsUser($driversPath);

		$this->files->putAsUser(
			$driversPath . '/SampleValetDriver.php',
			$this->files->getStub('SampleValetDriver.php')
		);
	}

	/**
	 * Create the Valet sites directory.
	 *
	 * @return void
	 */
	public function createSitesDirectory() {
		$this->files->ensureDirExists($this->valetHomePath('Sites'), user());
	}

	/**
	 * Create the directory for the Valet extensions.
	 *
	 * @return void
	 */
	public function createExtensionsDirectory() {
		$extensionsPath = $this->valetHomePath('Extensions');

		$this->files->ensureDirExists($extensionsPath, user());

		// Copy the extensions stubs to the Extensions directory.
		foreach ($this->files->scandir(__DIR__ . '/../stubs/extensions') as $file) {
			$this->files->putAsUser(
				"$extensionsPath/$file",
				$this->files->getStub("extensions/$file")
			);
		}
	}

	/**
	 * Create the directory for logs.
	 *
	 * @return void
	 */
	public function createLogDirectory() {
		$this->files->ensureDirExists($path = $this->valetHomePath('Log'), user());

		$this->files->touch($path . '/nginx-error.log');
	}

	/**
	 * Create the directory for SSL/TLS certificates.
	 *
	 * @return void
	 */
	public function createCertificatesDirectory() {
		$this->files->ensureDirExists($this->valetHomePath('Certificates'), user());
	}

	/**
	 * Create the directory for the Windows services.
	 *
	 * @return void
	 */
	public function createServicesDirectory() {
		$this->files->ensureDirExists($this->valetHomePath('Services'), user());
	}

	/**
	 * Write the base, initial configuration for Valet.
	 *
	 * @return void
	 */
	public function writeBaseConfiguration() {
		if (!$this->files->exists($this->path())) {
			$baseConfig = [
				'tld' => 'test',
				'paths' => [$this->valetHomePath('Sites')],
				'php_port' => PhpCgi::PORT,
				'php_xdebug_port' => PhpCgiXdebug::PORT,
				'share-tool' => 'ngrok'
			];

			$this->write($baseConfig);
		}

		$config = $this->read();

		// Add default_php if missing or is null.
		if (!isset($config['default_php']) || $config['default_php'] === null) {
			$this->addDefaultPhp();
		}

		// Add tld if missing.
		$this->updateKey('tld', $config['tld'] ?? 'test');
		// Add php_port if missing.
		$this->updateKey('php_port', $config['php_port'] ?? PhpCgi::PORT);
		// Add the default php_xdebug_port if missing.
		$this->updateKey('php_xdebug_port', $config['php_xdebug_port'] ?? PhpCgiXdebug::PORT);
		// Add share-tool if missing.
		$this->updateKey('share-tool', $config['share-tool'] ?? 'ngrok');
	}

	/**
	 * Forcefully delete the Valet home configuration directory and contents.
	 *
	 * @return void
	 */
	public function uninstall() {
		$this->files->unlink($this->valetHomePath());
	}

	/**
	 * Add the given php path to the configuration.
	 *
	 * @return void
	 */
	public function addDefaultPhp() {
		$phpPath = lcfirst(\PhpCgi::findDefaultPhpPath());

		$this->addPhp($phpPath);

		$php = $this->getPhp($phpPath);

		$this->updateKey('default_php', $php['version']);
	}

	/**
	 * Get the php configuration by path.
	 *
	 * @param string $phpPath
	 * @return mixed
	 */
	public function getPhp($phpPath) {
		$phpPath = str_replace('\\', "/", $phpPath);

		$config = $this->read();

		return collect($config['php'])->filter(function ($item) use ($phpPath) {
			return $phpPath === $item['path'];
		})->first();
	}

	/**
	 * Get the php configuration by version.
	 *
	 * @param string $phpVersion
	 * @return mixed
	 */
	public function getPhpByVersion($phpVersion) {
		$phpVersion = str_replace('\\', "/", $phpVersion);

		$config = $this->read();

		$php = collect($config['php'])->filter(function ($item) use ($phpVersion) {
			return $phpVersion === $item['version'] || $phpVersion === $item['version_alias'];
		})->first();

		if (empty($php)) {
			error("Cannot find PHP $phpVersion in the list.\nPlease make sure PHP $phpVersion is added to Valet with <bg=magenta> valet php:add </>", true);
		}

		return $php;
	}

	/**
	 * Determine if the given PHP version is the alias.
	 *
	 * @param string $phpVersion
	 * @return boolean
	 */
	public function isPhpAlias($phpVersion) {
		$php = $this->getPhpByVersion($phpVersion);
		return $php["version_alias"] === $phpVersion ? true : false;
	}

	/**
	 * Get the full version number of the PHP using the alias.
	 *
	 * @param string $phpVersionAlias The alias of the PHP version, eg. alias: 7.4, full: 7.4.33
	 *
	 * @return string The full version number, eg. 7.4.33
	 */
	public function getPhpFullVersionByAlias($phpVersionAlias) {
		return $this->getPhpByVersion($phpVersionAlias)["version"];
	}

	/**
	 * Add the given php path to the configuration.
	 *
	 * @param string $phpPath
	 * @return mixed
	 */
	public function addPhp($phpPath) {
		$phpPath = str_replace('\\', "/", $phpPath);

		$phpVersion = \PhpCgi::findPhpVersion($phpPath);

		if (!$phpVersion) {
			return false;
		}

		$config = $this->read();
		$config['php'] = $config['php'] ?? [];

		$existingPaths = collect($config['php'])->pluck('path')->toArray();
		$existingPorts = collect($config['php'])->pluck('port')->toArray();

		// don't want to overwrite existing config as there might be phpcgi service running for it
		// forcing user to run uninstall to stop services and remove entry
		if (in_array($phpPath, $existingPaths)) {
			warning("\nPHP path {$phpPath} already added to Valet");

			return null;
		}

		if (isset($config['php'][$phpVersion])) {
			warning("\nPHP version {$phpVersion} already added to Valet from this path {$phpPath}");

			return null;
		}

		if ($existingPorts) {
			rsort($existingPorts);
		}

		$phpPort = count($existingPorts) ? $existingPorts[0] + 1 : PhpCgi::PORT;
		$phpXdebugPort = $phpPort + 100;

		$config['php'][$phpVersion] = [
			'version' => $phpVersion,
			'version_alias' => number_format((float) $phpVersion, 1, '.', ''),
			'path' => $phpPath,
			'port' => $phpPort,
			'xdebug_port' => $phpXdebugPort
		];

		// Sort the PHP array by version number in descending order. Ie. 8.1.18, 8.1.8, 7.4.33
		krsort($config['php'], SORT_NATURAL);

		$this->write($config);

		return $config['php'][$phpVersion];
	}

	/**
	 * Remove the given php path from the configuration.
	 *
	 * @param string $phpPath
	 * @return mixed
	 */
	public function removePhp($phpPath) {
		$phpPath = str_replace('\\', "/", $phpPath);

		$config = $this->read();
		$config['php'] = $config['php'] ?? [];

		$existingPaths = collect($config['php'])->pluck('path')->toArray();
		$existingVersions = collect($config['php'])->pluck('port')->toArray();

		if (!in_array($phpPath, $existingPaths)) {
			warning("PHP path {$phpPath} not found in valet");

			return null;
		}

		$php = collect($config['php'])->filter(function ($item) use ($phpPath) {
			return $phpPath === $item['path'];
		})->first();

		if ($php['version'] === $config['default_php']) {
			error("Default PHP {$php['version']} cannot be removed");

			return null;
		}

		unset($config['php'][$php['version']]);

		$this->write($config);

		return $php;
	}

	/**
	 * Add the given path to the configuration.
	 *
	 * @param string $path
	 * @param bool $prepend
	 * @return void
	 */
	public function addPath(string $path, bool $prepend = false) {
		$path = str_replace('\\', "/", $path);
		$this->write(tap($this->read(), function (&$config) use ($path, $prepend) {
			$method = $prepend ? 'prepend' : 'push';

			$config['paths'] = collect($config['paths'])->{$method}($path)->unique()->all();
		}));
	}

	/**
	 * Prepend the given path to the configuration.
	 *
	 * @param string $path
	 * @return void
	 */
	public function prependPath(string $path) {
		$this->addPath($path, true);
	}

	/**
	 * Remove the given path from the configuration.
	 * Used by `valet forget`
	 *
	 * @param string $path
	 * @return void
	 */
	public function removePath(string $path) {
		if ($path == $this->valetHomePath('Sites')) {
			info("Cannot remove this directory because this is where Valet stores its site definitions.\nRun <bg=magenta> valet paths </> for a list of parked paths.");
			exit();
		}

		$this->write(tap($this->read(), function (&$config) use ($path) {
			$config['paths'] = collect($config['paths'])->reject(function ($value) use ($path) {
				return $value === $path;
			})->values()->all();
		}));
	}

	/**
	 * Prune all non-existent paths from the configuration.
	 *
	 * @return void
	 */
	public function prune() {
		if (!$this->files->exists($this->path())) {
			return;
		}

		$this->write(tap($this->read(), function (&$config) {
			$config['paths'] = collect($config['paths'])->filter(function ($path) {
				return $this->files->isDir($path);
			})->values()->all();
		}));
	}

	/**
	 * Read the configuration file as JSON.
	 *
	 * @return array
	 */
	public function read(): array {
		// If config.json file doesn't exist, then return empty array so Valet can setup a new one.
		if (!$this->files->exists($this->path())) {
			return [];
		}
		return json_decode($this->files->get($this->path()), true);
	}

	/**
	 * Get an item from the configuration file using "dot" notation.
	 *
	 * @param string|int|null $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($key, $default = null) {
		return Arr::get($this->read(), $key, $default);
	}

	/**
	 * Update a specific key in the configuration file.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return array
	 */
	public function updateKey(string $key, $value): array {
		return tap($this->read(), function (&$config) use ($key, $value) {
			$config[$key] = $value;

			$this->write($config);
		});
	}

	/**
	 * Write the given configuration to disk.
	 *
	 * @param array $config
	 * @return void
	 */
	public function write(array $config) {
		$this->files->putAsUser($this->path(), json_encode(
			$config,
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		) . PHP_EOL);
	}

	/**
	 * Get the configuration file path.
	 *
	 * @return string
	 */
	public function path(): string {
		return $this->valetHomePath('config.json');
	}

	/**
	 * Get the Valet home path.
	 *
	 * @param string $path
	 * @return string
	 */
	protected function valetHomePath(string $path = ''): string {
		return Valet::homePath($path);
	}
}