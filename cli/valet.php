<?php

/**
 * Load correct autoloader depending on install location.
 */
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
	require_once __DIR__ . '/../vendor/autoload.php';
} else {
	require_once __DIR__ . '/../../../autoload.php';
}

require_once __DIR__ . '/version.php';


use Illuminate\Container\Container;
use Silly\Application;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use function Valet\info;
use function Valet\info_dump;
use function Valet\output;
use function Valet\table;
use function Valet\default_table_headers;
use function Valet\warning;
use function Valet\error;
use function Valet\progressbar;

/**
 * Relocate config dir to ~/.config/valet/ if found in old location.
 */
if (is_dir(VALET_LEGACY_HOME_PATH) && !is_dir(VALET_HOME_PATH)) {
	Configuration::createConfigurationDirectory();
}

/**
 * Create the application.
 */
Container::setInstance(new Container);

$app = new Application('Laravel Valet Windows', $version);

/**
 * Prune missing directories and symbolic links on every command.
 */
if (is_dir(VALET_HOME_PATH)) {
	Configuration::prune();

	Site::pruneLinks();
}

// TODO [$64d587e2bcdbe5000925345e]: Check all descriptions and add descriptions for the args and options.

/**
 * Install Valet and any required services.
 */
$app->command('install [--xdebug]', function ($input, $output, $xdebug) {

	// TODO [$64d587e2bcdbe5000925345f]: Deprecate this question in version 3.0.3 and remove in 3.0.4
	$helper = $this->getHelperSet()->get('question');
	$question = new ConfirmationQuestion("<fg=red>Have you fully uninstalled the outdated cretueusebiu/valet-windows? yes/no</>\n", false);

	if (!$helper->ask($input, $output, $question)) {
		warning("Install aborted. \nPlease fully uninstall Valet from cretueusebiu and purge all configs.");
		output('<fg=yellow>Remove composer dependency with:</> <bg=magenta>composer global remove cretueusebiu/valet-windows</>');
		return;
	}

	$maxItems = $xdebug ? 6 : 5;
	$progressBar = progressbar($maxItems, "Installing");
	sleep(1);

	$progressBar->setMessage("Configuration", "placeholder");
	$progressBar->advance();
	Configuration::install();
	sleep(1);

	$progressBar->setMessage("Nginx", "placeholder");
	$progressBar->advance();
	Nginx::install();
	sleep(1);

	$progressBar->setMessage("PHP CGI", "placeholder");
	$progressBar->advance();
	PhpCgi::install();
	sleep(1);

	if ($xdebug) {
		$progressBar->setMessage("PHP CGI Xdebug", "placeholder");
		$progressBar->advance();
		PhpCgiXdebug::install();
		sleep(1);
	}

	$progressBar->setMessage("Acrylic", "placeholder");
	$progressBar->advance();
	Acrylic::install(Configuration::read()['tld']);
	sleep(1);

	$progressBar->setMessage("Ansicon", "placeholder");
	$progressBar->advance();
	Ansicon::install();
	sleep(1);

	$progressBar->finish();
	output(PHP_EOL . '<info>Valet installed and started successfully!</info>');

})->descriptions('Install and start the Valet services', [
			"--xdebug" => "Optionally, install Xdebug"
		]);

/**
 * A sudo-like command to use Valet commands with elevated privileges
 * that only require 1 User Account Control popup.
 *
 * @param array|null $valetCommand The Valet command plus the argument values. A string array separated by spaces.
 * @param string|null $valetOptions The Valet options without the leading `--`. Multiple options must be separated by double slashes `//`.
 * Example: `--valetOptions=isolate//secure` will be ran as `--isolate --secure`.
 *
 * @example `valet sudo link example --valetOptions=isolate//secure`
 */
$app->command('sudo valetCommand* [-o|--valetOptions=]', function ($valetCommand = null, $valetOptions = null) {
	if (!$valetCommand) {
		return;
	}
	$valetCommand = implode(" ", $valetCommand);

	$valetCommand = str_starts_with($valetCommand, 'valet') ? $valetCommand : "valet $valetCommand";

	if (str_contains($valetCommand, 'sudo')) {
		return error("You can not sudo the sudo command.");
	}

	if ($valetOptions != null) {
		$valetOptions = Valet\prefixOptions(explode("//", $valetOptions));
	}

	$valetCommand = implode(" ", [$valetCommand, $valetOptions]);

	CommandLine::sudo($valetCommand);

})->descriptions("A sudo-like command to use Valet commands with elevated privileges that only require 1 User Account Control popup.", [
			"valetCommand" => "The Valet command and its arguments and values, separated by spaces.",
			"--valetOptions" => "Specify options without the leading <fg=green>--</>. Multiple options must be separated by double slashes <fg=green>//</>."
		])->addUsage("sudo link mySite --valetOptions=isolate=7.4//secure")->addUsage("sudo link mySite -o isolate=7.4//secure");


/**
 * Add PHP.
 * @param string $path The php version path
 * @param bool $xdebug Optionally, install Xdebug
 */
$app->command('php:add path [--xdebug]', function ($path, $xdebug) {
	info("Adding {$path}...");

	if ($php = Configuration::addPhp($path)) {
		\PhpCgi::install($php['version']);

		if ($xdebug) {
			info("Installing Xdebug for {$php['version']}...");
			\PhpCgiXdebug::install($php['version']);
		}

		info("PHP {$php['version']} from {$path} has been added. You can make it default by running <bg=magenta> valet use </> command");
	}
})->descriptions('Add PHP by specifying a path')->addUsage("php:add c:/php/8.1")->addUsage("php:add c:/php/8.1 --xdebug");

/**
 * Remove PHP.
 */
$app->command('php:remove [phpVersion] [--path=]', function ($phpVersion, $path) {
	$txt = $phpVersion ? "version $phpVersion" : "path $path";
	info("Removing $txt...");

	$config = Configuration::read();
	$defaultPhp = $config['default_php'];

	$php = $phpVersion ? Configuration::getPhpByVersion($phpVersion) : Configuration::getPhp($path);

	if (!$php) {
		warning("PHP $txt not found in Valet");
		return;
	}

	if ($php['version'] === $defaultPhp) {
		warning("Default PHP {$php['version']} cannot be removed. Change default PHP version by running <bg=magenta> valet use [version] </>");

		return;
	}

	if ($php) {
		\PhpCgi::uninstall($php['version']);

		if (\PhpCgiXdebug::installed($php['version'])) {
			info("Uninstalling {$php['version']} Xdebug...");
			\PhpCgiXdebug::uninstall($php['version']);
		}
	}

	if (Configuration::removePhp($php['path'])) {
		info("PHP {$php['version']} from {$php['path']} has been removed.");
	}
})->descriptions('Remove PHP by specifying a path')->addUsage("php:remove 8.1")->addUsage("php:remove --path=c:/php/8.1");

/**
 * Install PHP services.
 */
$app->command('php:install', function () {
	info('Reinstalling services...');

	PhpCgi::uninstall();

	PhpCgi::install();
})->descriptions('Reinstall all PHP services from <fg=green>valet php:list</>');

/**
 * Uninstall PHP services.
 */
$app->command('php:uninstall', function () {
	info('Uninstalling PHP services...');

	PhpCgi::uninstall();

	info('PHP services uninstalled. Run php:install to install again');
})->descriptions('Uninstall all PHP services from <fg=green>valet php:list</>');

/**
 * List all PHP services.
 */
$app->command('php:list', function () {
	info('Listing PHP services...');

	$config = Configuration::read();
	$defaultPhpVersion = $config['default_php'];

	$php = $config['php'] ?? [];

	$php = collect($php)->map(function ($item) use ($defaultPhpVersion) {
		$item['default'] = $defaultPhpVersion === $item['version'] ? 'X' : '';

		return $item;
	})->toArray();

	table(['Version', 'Version Alias', 'Path', 'Port', 'Xdebug Port', 'Default'], $php, true);
})->descriptions('List all PHP services');

/**
 * Determines which PHP version the current working directory or a specified site is using.
 * @param null|string $site Optional site name
 */
$app->command('php:which [site]', function ($site = null) {
	$txt = !$site ? "The current working directory" : "The specified site";

	if (!$site) {
		$site = basename(getcwd());
	}

	$which = Site::whichPhp($site);

	if ($which === null) {
		warning("The site doesn't exist.");
		return false;
	}

	info("{$txt} {$which['site']} is using PHP {$which['php']}");

})->descriptions('Determine which PHP version the current working directory or a specified site is using');

/**
 * Install PHP Xdebug services.
 */
$app->command('xdebug:install [phpVersion]', function ($input, $output, $phpVersion = null) {

	$txt = $phpVersion === null ? "" : " for PHP $phpVersion";

	if (PhpCgiXdebug::installed($phpVersion)) {
		info("Xdebug{$txt} is already installed.");

		$helper = $this->getHelperSet()->get('question');
		$question = new ConfirmationQuestion("<fg=yellow>Do you want to reinstall it? yes/no</>\n", false);

		if (!$helper->ask($input, $output, $question)) {
			return warning('Install aborted.');
		}
	}

	info('Installing Xdebug services...');
	PhpCgiXdebug::install($phpVersion);

})->descriptions('Install all PHP Xdebug services from <fg=green>valet php:list</>', [
			"phpVersion" => "Optionally, install a specific PHP version of Xdebug"
		]);

/**
 * Uninstall PHP Xdebug services.
 */
$app->command('xdebug:uninstall [phpVersion]', function ($phpVersion = null) {
	if (!PhpCgiXdebug::installed($phpVersion)) {
		warning("Xdebug for PHP $phpVersion is not installed.");
		return;
	}

	PhpCgiXdebug::uninstall($phpVersion);

	info('Xdebug services uninstalled. Run <bg=gray>xdebug:install [phpVersion]</> to install again');

})->descriptions('Uninstall all PHP Xdebug services from <fg=green>valet php:list</>', [
			"phpVersion" => "Optionally, uninstall a specific PHP version of Xdebug"
		]);

/**
 * Most commands are available only if Valet is installed.
 */
if (is_dir(VALET_HOME_PATH)) {
	/**
	 * Upgrade helper: ensure the tld config exists.
	 */
	if (empty(Configuration::read()['tld'])) {
		Configuration::writeBaseConfiguration();
	}

	/**
	 * Get or set the TLD currently being used by Valet.
	 */
	$app->command('tld [tld]', function ($tld = null) {
		if ($tld === null) {
			return info(Configuration::read()['tld']);
		}

		$oldTld = Configuration::read()['tld'];

		Acrylic::updateTld($tld = trim($tld, '.'));

		Configuration::updateKey('tld', $tld);

		Site::resecureForNewTld($oldTld, $tld);
		Site::reisolateForNewTld($oldTld, $tld);

		PhpCgi::restart();

		if (PhpCgiXdebug::installed()) {
			PhpCgiXdebug::restart();
		}

		Nginx::restart();

		info('Your Valet TLD has been updated to [' . $tld . '].');
	}, ['domain'])->descriptions('Get or set the TLD used for Valet sites.');

	/**
	 * Add the current working directory to the paths configuration.
	 */
	$app->command('park [path]', function ($path = null) {
		Configuration::addPath($path ?: getcwd());

		info(($path === null ? 'This' : "The [{$path}]") . " directory has been added to Valet's paths.");
	})->descriptions('Register the current working (or specified) directory with Valet');

	/**
	 * Get all the current sites within paths parked with 'park {path}'.
	 */
	$app->command('parked', function () {
		$parked = Site::parked();

		table(default_table_headers(), $parked->all());
	})->descriptions('Display all the current sites within parked paths');

	/**
	 * Remove the current working directory from the paths configuration.
	 */
	$app->command('unpark|forget [path]', function ($path = null) {
		Configuration::removePath($path ?: getcwd());

		info(($path === null ? 'This' : "The [{$path}]") . " directory has been removed from Valet's paths.");
	}, ['unpark'])->descriptions('Remove the current working (or specified) directory from Valet\'s list of paths');

	/**
	 * Register the current working directory as a symbolic link with Valet.
	 *
	 * @param string $name Give the site an optional name instead of using the path.
	 * @param boolean $secure Secure the site with an TLS certificate.
	 * @param string $isolate Optionally provide a PHP version to isolate the symbolic link site.
	 */
	$app->command('link [name] [--secure] [--isolate=]', function ($name, $secure, $isolate = null) {
		$linkPath = Site::link(getcwd(), $name = $name ?: basename(getcwd()));

		info('A [' . $name . '] symbolic link has been created in [' . $linkPath . '].');

		if ($secure) {
			$this->runCommand('secure ' . $name);
		}

		if ($isolate) {
			Site::isolate($isolate, $name);
		}
	})->descriptions('Link the current working directory to Valet with a given name', [
				'--secure' => 'Optionally secure the site',
				'--isolate' => 'Isolate the site to a specified PHP version'
			])->addUsage("link mySite")->addUsage("link mySite --isolate=7.4")->addUsage("link mySite --secure")->addUsage("link mySite --secure --isolate=7.4");

	/**
	 * Display all of the registered symbolic links.
	 */
	$app->command('links', function () {
		$links = Site::links();

		// Recreate the list of links and remove the key of alias and aliasUrl,
		// as this is unnecessary for this command.
		$links = $links->map(function ($value, $key) {
			unset($value['alias']);
			unset($value['aliasUrl']);
			return $value;
		});

		table(['Site', 'Secured', 'PHP', 'URL', 'Path'], $links->all(), true);
	})->descriptions('Display all of the registered Valet symbolic links');

	/**
	 * Unlink a link from the Valet links directory.
	 */
	$app->command('unlink [name]', function ($name) {

		if (!$name) {
			$name = Site::getLinkNameByCurrentDir();
		}

		if (Site::isIsolated($name) === true) {
			Site::unisolate($name);
		}

		if (Site::isSecured($name) === true) {
			info('Unsecuring ' . $name . '...');

			$site = $name . '.' . Configuration::read()['tld'];
			Site::unsecure($site);

			Nginx::restart();
		}
		// Unlink the site.
		Site::unlink($name);

		info('The [' . $name . '] symbolic link has been removed.');

	})->descriptions('Remove the specified Valet symbolic link');

	/**
	 * Create an Nginx proxy config for the specified site.
	 */
	$app->command('proxy site host', function ($site, $host) {
		Site::proxyCreate($site, $host);
		Nginx::restart();
	})->descriptions('Create an Nginx proxy site for the specified host. Useful for docker, mailhog etc.');

	/**
	 * Display all of the sites that are proxies.
	 */
	$app->command('proxies', function () {
		$proxies = Site::proxies();

		table(['Site', 'Secured', 'URL', 'Host'], $proxies->all());
	})->descriptions('Display all of the proxy sites');

	/**
	 * Delete an Nginx proxy config.
	 */
	$app->command('unproxy site', function ($site) {
		Site::proxyDelete($site);
		Nginx::restart();
	})->descriptions('Delete an Nginx proxy config.');

	/**
	 * Get all the parked, linked and proxied sites.
	 */
	$app->command('sites', function () {
		$parked = Site::parked();
		$links = Site::links();
		$proxies = Site::proxies();

		// Recreate the list of links and remove the key of alias and aliasUrl,
		// as this is unnecessary for this command.
		$parked = $parked->map(function ($value, $key) {
			unset($value['secured']);
			unset($value['alias']);
			unset($value['aliasUrl']);

			$php = explode(" ", $value['php']);

			if (str_contains($php[0], "<info>")) {
				$php[0] = str_replace("<info>", "", $php[0]);
				$php[1] = str_replace("</info>", "", $php[1]);

				$value['php'] = "<info>{$php[0]}</info>" . "\n" . "<info>{$php[1]}</info>";
			} else {
				$value['php'] = $php[0] . "\n" . $php[1];
			}
			return $value;
		});

		$links = $links->map(function ($value, $key) {
			unset($value['secured']);
			unset($value['alias']);
			unset($value['aliasUrl']);
			return $value;
		});
		$proxies = $proxies->map(function ($value, $key) {
			unset($value['secured']);
			return $value;
		});

		if (count($parked) > 0) {
			table(['Site', 'PHP', 'URL', 'Path'], $parked->all(), true, "Parked");
		}
		if (count($links) > 0) {
			table(['Site', 'PHP', 'URL', 'Path'], $links->all(), true, "Linked");
		}
		if (count($proxies) > 0) {
			table(['Site', 'URL', 'Host'], $proxies->all(), true, "Proxied");
		}

	})->descriptions('Get all the parked, linked and proxied sites');

	/**
	 * Secure the given site with a trusted TLS certificate.
	 */
	$app->command('secure [site]', function ($site = null) {
		$url = Site::getSiteURL($site);

		Site::secure($url);

		Nginx::restart();

		info('The [' . $url . '] site has been secured with a fresh TLS certificate.');
	})->descriptions('Secure the given site with a trusted TLS certificate');

	/**
	 * Display all of the currently secured sites.
	 */
	$app->command('secured', function ($output) {
		$sites = collect(Site::secured())->map(function ($url) {
			return ['Site' => $url];
		});

		if (count($sites) === 0) {
			info("There are no secured sites.");
			return false;
		}

		table(['Site'], $sites->all(), true);
	})->descriptions('Display all of the currently secured sites');

	/**
	 * Stop serving the given site over HTTPS and remove the trusted TLS certificate.
	 */
	$app->command('unsecure [site] [--all]', function ($site = null, $all = null) {
		if ($all) {
			Site::unsecureAll();
			Nginx::restart();

			return;
		}

		$url = Site::getSiteURL($site);

		Site::unsecure($url);
		Nginx::restart();

		info('The [' . $url . '] site will now serve traffic over HTTP.');
	})->descriptions('Stop serving the given site over HTTPS and remove the trusted TLS certificate')->addUsage("unsecure mySite")->addUsage("unsecure --all");

	/**
	 * Set or change the default PHP version Valet uses.
	 *
	 *  @param string $phpVersion The PHP version eg. 8.1.8, or an alias eg. 8.1 to be set as the default
	 *
	 * ###### Note: If using the alias, and multiple versions of 8.1 are available eg. 8.1.8 and 8.1.18; then the most latest version will be used, eg. 8.1.18.
	 */
	$app->command('use [phpVersion]', function ($phpVersion) {
		if (empty($phpVersion)) {
			warning('Please enter a PHP version. Example command <bg=magenta> valet use 8.1 </>');

			return;
		}

		$php = Configuration::getPhpByVersion($phpVersion);

		info("Setting the default PHP version to [$phpVersion].");

		Configuration::updateKey('default_php', $php['version']);

		info('Stopping Nginx...');
		Nginx::stop();

		Nginx::installConfiguration();
		Nginx::installServer();
		Nginx::installNginxDirectory();
		Nginx::installService();
		Nginx::restart();

		info(sprintf('Valet is now using %s.', $php['version']) . PHP_EOL);
		info('Note that you might need to run <comment>composer global update</comment> if your PHP version change affects the dependencies of global packages required by Composer.');

	})->descriptions('Change the default version of PHP used by Valet', [
				'phpVersion' => 'The PHP version you want to use, e.g 8.1',
			]);

	/**
	 * Isolate the current working directory or a specified site to specific PHP version.
	 *
	 * @param string $phpVersion The PHP version you want to use, eg. "7.4.33"; or an alias, eg. "7.4"
	 * @param array $site The site you want to optionally specify, eg. "my-project" or "my-project.[tld]". If not specified, current working directory will be used.
	 * To specify multiple sites, you use it like:
	 * `--site=my-project --site=another-site`
	 */
	$app->command('isolate [phpVersion] [--site=]*', function ($phpVersion, $site = array()) {
		if (empty($phpVersion)) {
			warning('Please enter a PHP version. Example command <bg=magenta> valet isolate 7.4 </>');

			return;
		}

		// If $site is empty, then isolate the current working directory.
		if (!$site) {
			info("Isolating the current working directory...");
			Site::isolate($phpVersion, $site);

			return;
		}

		// Loop through the sites array and isolate each one.
		foreach ($site as $sitename) {
			Site::isolate($phpVersion, $sitename);
		}

	})->descriptions('Isolate the current working directory or a specified site(s) to a specific PHP version', [
				'phpVersion' => 'The PHP version you want to use; e.g 7.4',
				'--site' => 'Specify the site to isolate',
			])->addUsage("isolate 7.4 --site=mySite");

	/**
	 * List isolated sites.
	 */
	$app->command('isolated', function ($output) {

		$isolated = Site::isolated();

		if (count($isolated) === 0) {
			info("There are no isolated sites.");
			return;
		}

		table(["Site", "PHP"], $isolated->all(), true);

	})->descriptions('List isolated sites.');

	/**
	 * Remove [unisolate] an isolated site.
	 * @param string $phpVersion The PHP version you want to use, eg. "7.4.33"; or an alias, eg. "7.4"
	 * @param string $site The site you want to optionally specify, eg. "my-project" or "my-project.[tld]". If not specified, current working directory will be used.
	 *
	 * @param boolean|null $all
	 *
	 */
	$app->command('unisolate [--site=] [--all]', function ($output, $site = null, $all = null) {
		if ($all) {
			$isolated = Site::isolated();

			foreach ($isolated as $array) {
				Site::unisolate($array["site"]);
			}

			return;
		}

		if (!$site) {
			info("Unisolating the current working directory...");
		}

		Site::unisolate($site);

	})->descriptions('Remove [unisolate] an isolated site.', [
				'--site' => 'Specify the site to unisolate',
				'--all' => 'Optionally remove all isolated sites'
			])->addUsage("unisolate --site=mySite")->addUsage("unisolate --all");

	/**
	 * Determine which Valet driver the current directory is using.
	 */
	$app->command('which', function () {
		require __DIR__ . '/drivers/require.php';

		$driver = ValetDriver::assign(getcwd(), basename(getcwd()), '/');

		if ($driver) {
			info('This site is served by [' . get_class($driver) . '].');
		} else {
			warning('Valet could not determine which driver to use for this site.');
		}
	})->descriptions('Determine which Valet driver serves the current working directory');

	/**
	 * Display all of the registered paths.
	 */
	$app->command('paths', function () {
		$paths = Configuration::read()['paths'];

		if (count($paths) > 0) {
			output(json_encode($paths, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		} else {
			info('No paths have been registered.');
		}
	})->descriptions('Get all of the paths registered with Valet');

	/**
	 * Open the current or given directory in the browser.
	 */
	$app->command('open [site]', function ($site = null) {
		$url = 'http://' . ($site ?: Site::host(getcwd())) . '.' . Configuration::read()['tld'];
		CommandLine::passthru("start $url");
	})->descriptions('Open the site for the current (or specified) directory in your browser');

	/**
	 * Sharing - ngrok
	 */

	/**
	 * Set the ngrok auth token.
	 * @param string $token Your personal ngrok authtoken
	 * @return void
	 */
	$app->command('auth|set-ngrok-token [token]', function ($token = null) {
		if ($token === null) {
			warning("Please provide your ngrok authtoken.");
			return;
		}

		Ngrok::run("authtoken $token " . Ngrok::getNgrokConfig());

	})->descriptions('Set the ngrok auth token');

	/**
	 * Share a local site with a publically accessible URL.
	 *
	 * @param string $site Optionally, specify a site. Otherwise the default is the current working directory.
	 *
	 * @param string|null $options The options/flags of ngrok's `http` command to pass to ngrok.
	 * ---
	 *  Pass the option name without the `--` prefix (so Valet doesn't get confused with it's own options); eg. `--options domain=example.com`.
	 * If there's a space in the value you will need to surround the value in quotes.
	 * All options will be prefixed with `--` after Valet has processed the command.
	 * If the `host-header` option is not set, Valet will add it with the default of `rewrite`.
	 * Multiple options must be separated by double slashes `//`.
	 * ---
	 *
	 * The options `=` is optional:
	 * - `--options=[option]`
	 * - `--options [option]`
	 * - `-o [option]`
	 * ---
	 * `valet share mysite --options domain=example.com//region=eu//request-header-remove="blah blah"`
	 *
	 * @param bool $debug Allow debug error output
	 */
	$app->command(
		'share [site] [-o|--options=] [--debug]',
		function ($input, $site = null, $options = null, $debug) {

			// Send an error if the option shortcut has a =
			if (str_contains($input, "-o=")) {
				return error("Option shortcuts cannot have a <bg=magenta> = </> immediately after them.\nPlease use a space to separate it from the value: <bg=magenta> valet share $site -o " . preg_replace("/=/", "", $options, 1) . " </>");
			}

			$url = ($site ?: strtolower(Site::host(getcwd()))) . '.' . Configuration::read()['tld'];

			if ($options != null) {
				$options = explode("//", $options);
			}

			Ngrok::start($url, Site::port($url), $debug, $options);
		}
	)->descriptions('Share a local site with a publically accessible URL', [
				"site" => "Optionally, the site name, otherwise the default is the current working directory.",
				"--options" => "Specify ngrok options/flags without the leading <fg=green>--</>. Multiple options must be separated by double slashes <fg=green>//</>."
			])->addUsage("share mysite --options domain=example.com//region=eu")->addUsage("share -o domain=example.com");

	// TODO [$64d587e2bcdbe50009253460]: Share-tool for Expose (https://expose.dev/)
	// and 2 open-source clients like localtunnel (https://github.com/localtunnel/localtunnel)

	/**
	 * Get the public URL of the site that is currently being shared.
	 * @param string|null $site The site. If omitted, Valet will use the current working directory.
	 */
	$app->command('url|fetch-share-url [site]', function ($site = null) {
		$site = $site ?: Site::host(getcwd()) . '.' . Configuration::read()['tld'];

		$url = Ngrok::currentTunnelUrl($site);
		info("The public URL for $site is: <fg=blue>$url</>");
		info("It has been copied to your clipboard.");

	})->descriptions('Get the public URL of the site that is currently being shared.');

	/**
	 * Run ngrok commands.
	 *
	 * @param array $commands The ngrok commands plus arguments and values.
	 * eg. `valet ngrok config add-authtoken [token]`
	 * @param string|null $options The ngrok options/flags without the leading `--`. Multiple options must be separated by double slashes `//`.
	 *
	 * The options `=` is optional:
	 * - `--options=[option]`
	 * - `--options [option]`
	 * - `-o [option]`
	 *
	 * Example: `--options=config=C:/ngrok.yml//help` will be ran as `--config=C:/ngrok.yml --help`.
	 *
	 * @example `valet ngrok config add-authtoken [token] --options config=C:/ngrok.yml//help`
	 */
	$app->command('ngrok [commands]* [-o|--options=]', function ($input, $commands, $options = null) {

		// Send an error if the option shortcut has a =
		if (str_contains($input, "-o=")) {
			return error("Option shortcuts cannot have a <bg=magenta> = </> immediately after them.\nPlease use a space to separate it from the value: <bg=magenta> valet ngrok " . implode(" ", $commands) . " -o " . preg_replace("/=/", "", $options, 1) . " </>");
		}

		if ($options != null) {
			$options = Valet\prefixOptions(explode("//", $options));
		}

		$commands = implode(" ", [implode(" ", $commands), $options]);

		Ngrok::run($commands);

	})->descriptions('Run ngrok commands', [
				"commands" => "The ngrok command and its arguments and values, separated by spaces.",
				"--options" => "Specify ngrok options/flags without the leading <fg=green>--</>. Multiple options must be separated by double slashes <fg=green>//</>."
			])->addUsage("ngrok config add-authtoken [token] --options config=C:/ngrok.yml")->addUsage("ngrok config add-authtoken [token] -o config=C:/ngrok.yml");

	/**
	 * Start the daemon services.
	 */
	$app->command('start [service]', function ($service) {
		$this->runCommand('restart ' . $service . ' --dev-txt=started');
	})->descriptions('Start the Valet services');

	/**
	 * Restart the daemon services.
	 */
	$app->command('restart [service] [--dev-txt=]', function ($service, $devTxt = "restarted") {

		if (!in_array($devTxt, ["started", "restarted"])) {
			warning("The <bg=gray>--dev-txt</> option is for internal use only. Please don't use it, it doesn't do anything.");
			return;
		}

		switch ($service) {
			case '':
				$progressBar = progressbar(3, $devTxt === "started" ? "Starting" : "Restarting");
				sleep(1);

				$progressBar->setMessage('Acrylic', "placeholder");
				$progressBar->advance();
				Acrylic::restart();
				sleep(1);

				$progressBar->setMessage('PHP CGI', "placeholder");
				$progressBar->advance();
				PhpCgi::restart();
				sleep(1);

				if (PhpCgiXdebug::installed()) {
					$progressBar->setMaxSteps(4);

					$progressBar->setMessage('PHP CGI Xdebug', "placeholder");
					$progressBar->advance();
					PhpCgiXdebug::restart();
					sleep(1);
				}

				$progressBar->setMessage('Nginx', "placeholder");
				$progressBar->advance();
				Nginx::restart();
				sleep(1);

				$progressBar->finish();
				return info("\nValet services have been $devTxt.");

			case 'acrylic':
				Acrylic::restart();

				return info("Acrylic DNS has been $devTxt.");
			case 'nginx':
				Nginx::restart();

				return info("Nginx has been $devTxt.");
			case 'php':
				PhpCgi::restart();

				return info("PHP has been $devTxt.");
			case 'php-xdebug':
				PhpCgiXdebug::restart();

				return info("PHP Xdebug has been $devTxt.");
		}

		return warning(sprintf('Invalid Valet service name [%s]', $service));

	})->descriptions('Restart the Valet services', [
				"service" => "The Valet service name [acrylic, nginx, php, php-xdebug]",
				"--dev-txt" => "INTERNAL USE ONLY"
			]);

	/**
	 * Stop the daemon services.
	 */
	$app->command('stop [service]', function ($service) {
		switch ($service) {
			case '':
				$progressBar = progressbar(3, "Stopping");
				sleep(1);

				$progressBar->setMessage("Acrylic", "placeholder");
				$progressBar->advance();
				Acrylic::stop();
				sleep(1);

				$progressBar->setMessage("Nginx", "placeholder");
				$progressBar->advance();
				Nginx::stop();
				sleep(1);

				$progressBar->setMessage("PHP CGI", "placeholder");
				$progressBar->advance();
				PhpCgi::stop();
				sleep(1);

				if (PhpCgiXdebug::installed()) {
					$progressBar->setMaxSteps(4);

					$progressBar->setMessage('PHP CGI Xdebug', "placeholder");
					$progressBar->advance();
					PhpCgiXdebug::stop();
					sleep(1);
				}

				$progressBar->finish();
				return info("\nValet services have been stopped.");

			case 'acrylic':
				Acrylic::stop();

				return info('Acrylic DNS has been stopped.');
			case 'nginx':
				Nginx::stop();

				return info('Nginx has been stopped.');
			case 'php':
				PhpCgi::stop();

				return info('PHP has been stopped.');
			case 'php-xdebug':
				PhpCgiXdebug::stop();

				return info('PHP Xdebug has been stopped.');
		}

		return warning(sprintf('Invalid Valet service name [%s]', $service));
	})->descriptions('Stop the Valet services');

	/**
	 * Determine if this is the latest release of Valet.
	 */
	$app->command('latest|on-latest-version', function () use ($version) {
		if (Valet::onLatestVersion($version)) {
			info('Yes');
		} else {
			warning(sprintf('Your version of Valet Windows (%s) is not the latest version available.', $version));
			output("You can use <fg=cyan>composer global update</> to update to the latest release (after uninstalling Valet first).\nPlease read the documentation and the Changelog for information on possible breaking changes.");
		}
	})->descriptions('Determine if this is the latest version of Valet');

	/**
	 * Tail log file.
	 */
	$app->command('log [-f|--follow] [-l|--lines=] [key]', function ($follow, $lines, $key = null) {
		$defaultLogs = [
			'nginx' => VALET_HOME_PATH . '/Log/nginx-error.log',
			'nginxservice.err' => VALET_HOME_PATH . '/Log/nginxservice.err.log',
			'nginxservice.out' => VALET_HOME_PATH . '/Log/nginxservice.out.log',
			'nginxservice.wrapper' => VALET_HOME_PATH . '/Log/nginxservice.wrapper.log',
			'phpcgiservice.err' => VALET_HOME_PATH . '/Log/phpcgiservice.err.log',
			'phpcgiservice.out' => VALET_HOME_PATH . '/Log/phpcgiservice.out.log',
			'phpcgiservice.wrapper' => VALET_HOME_PATH . '/Log/phpcgiservice.wrapper.log',
		];

		$configLogs = data_get(Configuration::read(), 'logs');
		if (!is_array($configLogs)) {
			$configLogs = [];
		}

		$logs = array_merge($defaultLogs, $configLogs);
		ksort($logs);

		if (!$key) {
			info(implode(PHP_EOL, [
				'In order to tail a log, pass the relevant log key (e.g. "nginx")',
				'along with any optional tail parameters (e.g. "-f" for follow).',
				null,
				'For example: <bg=magenta> valet log nginx -f --lines=3 </>',
				null,
				'Here are the logs you might be interested in.',
				null,
			]));

			table(
				['Key', 'File'],
				collect($logs)->map(function ($file, $key) {
					return [$key, $file];
				})->toArray()
			);

			info(implode(PHP_EOL, [
				null,
				'Tip: Set custom logs by adding a "logs" key/file object',
				'to your "' . Configuration::path() . '" file.',
			]));

			exit;
		}

		if (!isset($logs[$key])) {
			return warning('No logs found for [' . $key . '].');
		}

		$file = $logs[$key];
		if (!file_exists($file)) {
			return warning('Log path [' . $file . '] does not (yet) exist.');
		}

		$options = [];
		if ($follow) {
			$options[] = '-f';
		}
		if ((int) $lines) {
			$options[] = '-n ' . (int) $lines;
		}

		$command = implode(' ', array_merge(['tail'], $options, [$file]));

		passthru($command);
	})->descriptions('Tail log file');

	/**
	 * List the installed Valet services.
	 */
	$app->command('services', function () {
		info("Checking the Valet services...");

		$services = Valet::services();
		output("\n");

		table(['Service', 'Windows Name', 'Status'], $services, true);
		info('Use <bg=magenta> start </> <bg=magenta> stop </> or <bg=magenta> restart </> commands to change the status, eg. <bg=magenta> valet restart nginx </>');

	})->descriptions('List the installed Windows services.');

	/**
	 * Configure or display the directory-listing setting.
	 */
	$app->command('directory-listing [status]', function ($status = null) {
		$key = 'directory-listing';
		$config = Configuration::read();

		if (in_array($status, ['on', 'off'])) {
			$config[$key] = $status;
			Configuration::write($config);

			return output('Directory listing setting is now: ' . $status);
		}

		$current = isset($config[$key]) ? $config[$key] : 'off';
		output('Directory listing is ' . $current);
	})->descriptions('Determine directory-listing behavior. Default is off, which means a 404 will display.', [
				'status' => 'on or off. (default=off) will show a 404 page; [on] will display a listing if project folder exists but requested URI not found',
			]);

	/**
	 * Output diagnostics to aid in debugging Valet.
	 */
	$app->command('diagnose [-p|--print] [--plain]', function ($print, $plain) {
		info('Running diagnostics... (this may take a while)');

		Diagnose::run($print, $plain);
	})->descriptions('Output diagnostics to aid in debugging Valet.', [
				'--print' => 'print diagnostics output while running',
				'--plain' => 'format clipboard output as plain text',
			]);

	/**
	 * Uninstall Valet entirely. Requires --force to actually remove; otherwise manual instructions are displayed.
	 */
	$app->command('uninstall [--force] [--purge-config]', function ($input, $output, $force, $purgeConfig) {

		$helper = $this->getHelperSet()->get('question');

		if (!$force) {
			warning('YOU ARE ABOUT TO UNINSTALL Nginx, PHP-CGI, Acrylic DNS, Ansicon, and all Valet configs and logs.');
			usleep(300000); // 0.3s

			$question = new ConfirmationQuestion("Are you sure you want to proceed? yes/no\n", false);

			if (!$helper->ask($input, $output, $question)) {
				return warning('Uninstall aborted.');
			}
		}

		$maxItems = PhpCgiXdebug::installed() ? 4 : 3;
		$progressBar = progressbar($maxItems, "Stopping");
		sleep(1);

		$progressBar->setMessage("Acrylic", "placeholder");
		$progressBar->advance();
		Acrylic::stop();
		sleep(1);

		$progressBar->setMessage("Nginx", "placeholder");
		$progressBar->advance();
		Nginx::stop();
		sleep(1);

		$progressBar->setMessage("PHP CGI", "placeholder");
		$progressBar->advance();
		PhpCgi::stop();
		sleep(1);

		if (PhpCgiXdebug::installed()) {
			$progressBar->setMessage('PHP CGI Xdebug', "placeholder");
			$progressBar->advance();
			PhpCgiXdebug::stop();
			sleep(1);
		}

		$progressBar->finish();

		if ($purgeConfig) {
			info("\nRemoving certificates for all secured sites...");
			Site::unsecureAll();
			sleep(1);

		} else {
			Site::untrustCertificates();
		}
		$progressBar->clear();

		// INFO: Uninstallation...

		$progressBar = progressbar($maxItems + 1, "Uninstalling");
		sleep(1);

		$progressBar->setMessage("Nginx", "placeholder");
		$progressBar->advance();
		Nginx::uninstall();
		sleep(1);

		$progressBar->setMessage("Acrylic", "placeholder");
		$progressBar->advance();
		Acrylic::uninstall();
		sleep(1);

		$progressBar->setMessage("PHP CGI", "placeholder");
		$progressBar->advance();
		PhpCgi::uninstall();
		sleep(1);

		if (PhpCgiXdebug::installed()) {
			$progressBar->setMessage('PHP CGI Xdebug', "placeholder");
			$progressBar->advance();
			PhpCgiXdebug::uninstall();
			sleep(1);
		}

		$progressBar->setMessage("Ansicon", "placeholder");
		$progressBar->advance();
		Ansicon::uninstall();
		sleep(1);

		if ($purgeConfig) {
			$progressBar->setMaxSteps(6);

			$progressBar->setMessage("Configuration", "placeholder");
			$progressBar->advance();
			Configuration::uninstall();
			sleep(1);
		}

		$progressBar->finish();
		info("\nValet has been removed from your system.");

		output(
			"\n<fg=yellow>NOTE:</>" .
			"\nRemove composer dependency with: <bg=magenta>composer global remove ycodetech/valet-windows</>" .
			($purgeConfig ? '' : "\nDelete the config files from: <info>~/.config/valet</info>") .
			"\nDelete PHP from: <info>C:/php</info>"
		);
	})->descriptions('Uninstall the Valet services', ['--force' => 'Force an uninstall without confirmation.']);
}

/**
 * Load all of the Valet extensions.
 */
foreach (Valet::extensions() as $extension) {
	include $extension;
}

/**
 * Run the application.
 */
$app->run();
