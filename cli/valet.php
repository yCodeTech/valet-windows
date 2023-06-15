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

/**
 * Add PHP.
 */
$app->command('php:add [path]', function ($path) {
	info("Adding {$path}...");

	if ($php = Configuration::addPhp($path)) {
		\PhpCgi::install($php['version']);

		info("PHP {$php['version']} from {$path} has been added. You can make it default by running `valet use` command");
	}
})->descriptions('Add PHP by specifying a path');

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
		warning("PHP $txt not found in valet");
		return;
	}

	if ($php['version'] === $defaultPhp) {
		warning("Default PHP {$php['version']} cannot be removed. Change default PHP version by running [valet use VERSION]");

		return;
	}

	if ($php) {
		\PhpCgi::uninstall($php['version']);

		// TODO: Change commands to install php xdebug services only when the xbug command is used instead of automatically installing, especially when they're not be used. And only uninstall/restart them if they're added.

		// \PhpCgiXdebug::uninstall($php['version']);
	}

	if (Configuration::removePhp($php['path'])) {
		info("PHP {$php['version']} from {$php['path']} has been removed.");
	}
})->descriptions('Remove PHP by specifying a path');

/**
 * Install PHP services.
 */
$app->command('php:install', function () {
	info('Reinstalling services...');

	PhpCgi::uninstall();

	PhpCgi::install();
})->descriptions('Reinstall all PHP services from [valet php:list]');

/**
 * Uninstall PHP services.
 */
$app->command('php:uninstall', function () {
	info('Uninstalling PHP services...');

	PhpCgi::uninstall();

	info('PHP services uninstalled. Run php:install to install again');
})->descriptions('Uninstall all PHP services from [valet php:list]');

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

	table(['Version', 'Version Alias', 'Path', 'Port', 'xDebug Port', 'Default'], $php, true);
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
 * Install PHP xDebug services.
 */
$app->command('xdebug:install', function () {
	info('Reinstalling xDebug services...');

	PhpCgiXdebug::uninstall();

	PhpCgiXdebug::install();
})->descriptions('Reinstall all PHP xDebug services from [valet php:list]');

/**
 * uninstall PHP xDebug services.
 */
$app->command('xdebug:uninstall', function () {
	info('Uninstalling xDebug services...');

	PhpCgiXdebug::uninstall();

	info('xDebug services uninstalled. Run xdebug:install to install again');
})->descriptions('Uninstall all PHP xDebug services from [valet php:list]');

/**
 * A sudo-like command to use valet commands with elevated privileges that only require 1 User Account Control popup.
 */
$app->command('sudo [valetCommand]*', function ($valetCommand = null) {
	if (!$valetCommand) {
		return;
	}
	$valetCommand = implode(" ", $valetCommand);

	$valetCommand = str_starts_with($valetCommand, 'valet') ? $valetCommand : "valet $valetCommand";

	CommandLine::sudo($valetCommand);
});

/**
 * Install Valet and any required services.
 */
$app->command('install', function () {
	Configuration::install();
	Nginx::install();
	PhpCgi::install();
	// PhpCgiXdebug::install();
	Acrylic::install(Configuration::read()['tld']);

	output(PHP_EOL . '<info>Valet installed successfully! Please use `valet start` to start the services.</info>');
})->descriptions('Install the Valet services');

/**
 * Most commands are available only if valet is installed.
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
		PhpCgi::restart();
		// PhpCgiXdebug::restart();
		Nginx::restart();

		info('Your Valet TLD has been updated to [' . $tld . '].');
	}, ['domain'])->descriptions('Get or set the TLD used for Valet sites.');

	/**
	 * Add the current working directory to the paths configuration.
	 */
	$app->command('park [path]', function ($path = null) {
		Configuration::addPath($path ?: getcwd());

		//        Site::publishParkedNginxConf($path ?: getcwd());

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
	 * Remove the current working directory from the paths configuration.
	 */
	$app->command('forget [path]', function ($path = null) {
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
			]);

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
	 * Secure the given domain with a trusted TLS certificate.
	 */
	$app->command('secure [domain]', function ($domain = null) {
		$url = Site::getSiteURL($domain);

		Site::secure($url);

		Nginx::restart();

		info('The [' . $url . '] site has been secured with a fresh TLS certificate.');
	})->descriptions('Secure the given domain with a trusted TLS certificate');

	/**
	 * Stop serving the given domain over HTTPS and remove the trusted TLS certificate.
	 */
	$app->command('unsecure [domain] [--all]', function ($domain = null, $all = null) {
		if ($all) {
			Site::unsecureAll();
			Nginx::restart();

			return;
		}

		$url = Site::getSiteURL($domain);

		Site::unsecure($url);
		Nginx::restart();

		info('The [' . $url . '] site will now serve traffic over HTTP.');
	})->descriptions('Stop serving the given domain over HTTPS and remove the trusted TLS certificate');

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
	 * Create an Nginx proxy config for the specified domain.
	 */
	$app->command('proxy domain host', function ($domain, $host) {
		Site::proxyCreate($domain, $host);
		Nginx::restart();
	})->descriptions('Create an Nginx proxy site for the specified host. Useful for docker, mailhog etc.');

	/**
	 * Delete an Nginx proxy config.
	 */
	$app->command('unproxy domain', function ($domain) {
		Site::proxyDelete($domain);
		Nginx::restart();
	})->descriptions('Delete an Nginx proxy config.');

	/**
	 * Display all of the sites that are proxies.
	 */
	$app->command('proxies', function () {
		$proxies = Site::proxies();

		table(['Site', 'Secured', 'URL', 'Host'], $proxies->all());
	})->descriptions('Display all of the proxy sites');

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
	$app->command('open [domain]', function ($domain = null) {
		$url = 'http://' . ($domain ?: Site::host(getcwd())) . '.' . Configuration::read()['tld'];
		CommandLine::passthru("start $url");
	})->descriptions('Open the site for the current (or specified) directory in your browser');

	/**
	 * Generate a publicly accessible URL for your project.
	 * @param string $site The site
	 * @param array $options The options/flags to pass to ngrok
	 * @param bool $debug Allow debug error output
	 */
	$app->command(
		'share [site] [options]* [--debug]',
		function ($site = null, $options = [], $debug) {

			$url = ($site ?: strtolower(Site::host(getcwd()))) . '.' . Configuration::read()['tld'];

			Ngrok::start($url, Site::port($url), $debug, $options);
		}
	)->descriptions('Generate a publicly accessible URL for your project');

	// TODO: Share-tool for Expose (https://expose.dev/) 
	// and 2 open-source clients like localtunnel (https://github.com/localtunnel/localtunnel)

	/**
	 * Echo the currently tunneled URL.
	 */
	$app->command('fetch-share-url [site]', function ($site = null) {
		$site = $site ?: Site::host(getcwd()) . '.' . Configuration::read()['tld'];

		$url = Ngrok::currentTunnelUrl($site);
		info("The public URL for $site is: <fg=blue>$url</>");
		info("It has been copied to your clipboard.");

	})->descriptions('Get the URL to the current Ngrok tunnel');

	/**
	 * Run ngrok commands.
	 * 
	 * @param array $commands The ngrok commands and options/flags (without the `--` prefix),
	 * eg. `valet ngrok config add-authtoken [token] config=C:/ngrok.yml`
	 */
	$app->command('ngrok [commands]*', function ($commands) {
		Ngrok::run(Ngrok::prefixNgrokFlags($commands));
	})->descriptions('Run ngrok commands');

	/**
	 * Set the ngrok auth token.
	 * @param string $token Your personal ngrok authtoken
	 * @return void
	 */
	$app->command('set-ngrok-token [token]', function ($token = null) {
		if ($token === null) {
			warning("Please provide your ngrok authtoken.");
			return;
		}

		Ngrok::run("authtoken $token " . Ngrok::getNgrokConfig());

	})->descriptions('Set the Ngrok auth token');

	/**
	 * Start the daemon services.
	 */
	$app->command('start [service]', function ($service) {
		switch ($service) {
			case '':
				Acrylic::restart();
				PhpCgi::restart();
				// PhpCgiXdebug::restart();
				Nginx::restart();

				return info('Valet services have been started.');
			case 'acrylic':
				Acrylic::restart();

				return info('Acrylic DNS has been started.');
			case 'nginx':
				Nginx::restart();

				return info('Nginx has been started.');
			case 'php':
				PhpCgi::restart();

				return info('PHP has been started.');
			case 'php-xdebug':
				PhpCgiXdebug::restart();

				return info('PHP Xdebug has been started.');
		}

		return warning(sprintf('Invalid valet service name [%s]', $service));
	})->descriptions('Start the Valet services');

	/**
	 * Restart the daemon services.
	 * TODO: Change start and restart to share the same function instead of duplicating code.
	 */
	$app->command('restart [service]', function ($service) {
		switch ($service) {
			case '':
				Acrylic::restart();
				PhpCgi::restart();
				// PhpCgiXdebug::restart();
				Nginx::restart();

				return info('Valet services have been restarted.');
			case 'acrylic':
				Acrylic::restart();

				return info('Acrylic DNS has been restarted.');
			case 'nginx':
				Nginx::restart();

				return info('Nginx has been restarted.');
			case 'php':
				PhpCgi::restart();

				return info('PHP has been restarted.');
			case 'php-xdebug':
				PhpCgiXdebug::restart();

				return info('PHP Xdebug has been restarted.');
		}

		return warning(sprintf('Invalid valet service name [%s]', $service));
	})->descriptions('Restart the Valet services');

	/**
	 * Stop the daemon services.
	 */
	$app->command('stop [service]', function ($service) {
		switch ($service) {
			case '':
				Acrylic::stop();
				Nginx::stop();
				PhpCgi::stop();
				// PhpCgiXdebug::stop();

				return info('Valet services have been stopped.');
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

		return warning(sprintf('Invalid valet service name [%s]', $service));
	})->descriptions('Stop the Valet services');

	/**
	 * Uninstall Valet entirely. Requires --force to actually remove; otherwise manual instructions are displayed.
	 */
	$app->command('uninstall [--force] [--purge-config]', function ($input, $output, $force, $purgeConfig) {
		warning('YOU ARE ABOUT TO UNINSTALL Nginx, PHP-CGI, Acrylic DNS and all Valet configs and logs.');

		$helper = $this->getHelperSet()->get('question');
		$question = new ConfirmationQuestion("Are you sure you want to proceed? yes/no\n", false);
		if (!$force && !$helper->ask($input, $output, $question)) {
			return warning('Uninstall aborted.');
		}

		info('Stopping services...');

		Acrylic::stop();
		Nginx::stop();
		PhpCgi::stop();
		// PhpCgiXdebug::stop();

		if ($purgeConfig) {
			info('Removing certificates for all secured sites...');
			Site::unsecureAll();
		} else {
			Site::untrustCertificates();
		}

		info('Removing Nginx...');
		Nginx::uninstall();

		info('Removing Acrylic DNS...');
		Acrylic::uninstall();

		info('Removing PHP-CGI...');
		PhpCgi::uninstall();

		// info('Removing PHP-CGI Xdebug...');
		// PhpCgiXdebug::uninstall();

		if ($purgeConfig) {
			info('Removing Valet configs...');
			Configuration::uninstall();
		}

		info("\nValet has been removed from your system.");

		output(
			"\n<fg=yellow>NOTE:</>" .
			"\nRemove composer dependency with: <info>composer global remove cretueusebiu/valet-windows</info>" .
			($purgeConfig ? '' : "\nDelete the config files from: <info>~/.config/valet</info>") .
			"\nDelete PHP from: <info>C:/php</info>"
		);
	})->descriptions('Uninstall the Valet services', ['--force' => 'Do a forceful uninstall of Valet']);

	/**
	 * Determine if this is the latest release of Valet.
	 */
	$app->command('on-latest-version', function () use ($version) {
		if (Valet::onLatestVersion($version)) {
			info('Yes');
		} else {
			warning(sprintf('Your version of Valet Windows (%s) is not the latest version available.', $version));
			output("You can use <fg=cyan>composer global update</> to update to the latest release.\nPlease read the documentation and the Changelog for information on possible breaking changes.");
		}
	})->descriptions('Determine if this is the latest version of Valet');

	/**
	 * Set or change the default PHP version valet uses.
	 *
	 *  @param string $phpVersion The PHP version eg. 8.1.8, or an alias eg. 8.1 to be set as the default
	 *
	 * ###### Note: If using the alias, and mulitple versions of 8.1 are available eg. 8.1.8 and 8.1.18; then the most latest version will be used, eg. 8.1.18.
	 */
	$app->command('use [phpVersion]', function ($phpVersion) {
		if (empty($phpVersion)) {
			warning('Please enter a PHP version. Example command [valet use 8.1]');

			return;
		}

		$php = Configuration::getPhpByVersion($phpVersion);

		if (empty($php)) {
			warning("Cannot find PHP [$phpVersion] in the list. Example command [valet use 8.1]");
			return false;
		}

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

	})->descriptions('Change the default version of PHP used by valet', [
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
			warning('Please enter a PHP version. Example command [valet isolate 7.4]');

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
			]);

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
			]);

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
				'For example: "valet log nginx -f --lines=3"',
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

		table(['Service', 'Windows Name', 'Status'], Valet::services(), true);
		info('Use valet start/stop/restart [service] to change status (eg: valet restart nginx).');
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