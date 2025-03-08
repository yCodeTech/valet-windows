<?php

/**
 * Load correct autoloader depending on install location.
 */
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
	require_once __DIR__ . '/../vendor/autoload.php';
}
else {
	require_once __DIR__ . '/../../../autoload.php';
}

require_once __DIR__ . '/version.php';


use Illuminate\Container\Container;
use Valet\Application;
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
 * Create the application.
 */
Container::setInstance(new Container());

$app = new Application('Laravel Valet Windows', $version);

/**
 * Prune missing directories and symbolic links on every command.
 */
if (is_dir(VALET_HOME_PATH)) {
	Configuration::prune();

	Site::pruneLinks();
}

/**
 * Install Valet's services and configs,
 * and auto start Valet.
 * @param boolean $xdebug Optionally, install Xdebug for PHP
 */
$app->command('install [--xdebug]', function ($input, $output, $xdebug) {
	$helper = $this->getHelperSet()->get('question');

	/**
	 * Check if the Valet is already installed by checking if the services are running.
	 */
	$services = Valet::services(true);
	$alreadyInstalled = false;

	foreach ($services as $key => $value) {
		if (str_contains($value["status"], "running")) {
			$alreadyInstalled = true;
			break;
		};
	}

	if ($alreadyInstalled) {
		$alreadyInstalledQuestion = new ConfirmationQuestion("<fg=red>Valet seems to already be installed. Do you want to reinstall? yes/no</fg=red>\n", false);

		if (!$helper->ask($input, $output, $alreadyInstalledQuestion)) {
			warning("Reinstall aborted.");
			return;
		}
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

})->descriptions("Install Valet's services and configs, and auto start Valet.", [
	"--xdebug" => "Optionally, install Xdebug for PHP"
])->addUsage("install --xdebug");

/**
 * A sudo-like command to use Valet commands with elevated privileges
 * that only require 1 User Account Control popup.
 *
 * @param array|null $valetCommand The Valet command plus it's argument's values. A string array separated by spaces.
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

	if (str_contains($valetCommand, 'sudo')) {
		return error("You can not sudo the sudo command.");
	}

	$valetCommand = str_starts_with($valetCommand, 'valet') ? $valetCommand : "valet $valetCommand";

	if ($valetOptions != null) {
		$valetOptions = Valet\prefixOptions(explode("//", $valetOptions));
	}

	$valetCommand = implode(" ", [$valetCommand, $valetOptions]);

	CommandLine::sudo($valetCommand);

})->descriptions("A sudo-like command to use Valet commands with elevated privileges that only require 1 User Account Control popup.", [
	"valetCommand" => "The Valet command plus it's argument's values, separated by spaces.",
	"--valetOptions" => "The Valet options without the leading <fg=green>--</>. Multiple options must be separated by double slashes <fg=green>//</>."
])->addUsage("sudo link mySite --valetOptions=isolate=7.4//secure")->addUsage("sudo link mySite -o isolate=7.4//secure");

/**
 * Output diagnostics to aid in debugging Valet.
 * @param boolean $print Optionally print diagnostics output while running
 * @param boolean $plain Optionally format clipboard output as plain text
 */
$app->command('diagnose [-p|--print] [--plain]', function ($print, $plain) {
	info('Running diagnostics... (this may take a while)');

	Diagnose::run($print, $plain);

	info("The diagnostics have been copied to your clipboard.");
})->descriptions('Output diagnostics to aid in debugging Valet.', [
	'--print' => 'Print diagnostics output while running',
	'--plain' => 'Print and format output as plain text (aka, pretty print)'
]);

/**
 * Add PHP by specifying a path.
 * @param string $path The path to the PHP
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
})->descriptions('Add PHP by specifying a path', [
	"path" => "The path to the PHP",
	"--xdebug" => "Optionally, install Xdebug"
])->addUsage("php:add c:/php/8.1")->addUsage("php:add c:/php/8.1 --xdebug");

/**
 * Remove PHP by specifying it's version.
 * @param string $phpVersion The PHP version
 * @param string $path Optionally, specify the path to the PHP, instead of using the `$phpVersion`.
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
})->descriptions('Remove PHP by specifying the version', [
	"phpVersion" => "The PHP version",
	"--path" => "Optionally, specify the path to the PHP, instead of using the <fg=green>phpVersion</>"
])->addUsage("php:remove 8.1")->addUsage("php:remove 8.1.8")->addUsage("php:remove --path=c:/php/8.1");

/**
 * Reinstall all PHP services that are specified in `valet php:list`
 */
$app->command('php:install', function () {
	info('Reinstalling PHP services...');

	PhpCgi::uninstall();

	PhpCgi::install();
})->descriptions('Reinstall all PHP services from <fg=green>valet php:list</>');

/**
 * Uninstall all PHP services that are specified in `valet php:list`
 */
$app->command('php:uninstall', function () {
	info('Uninstalling PHP services...');

	PhpCgi::uninstall();

	info('PHP services uninstalled. Run php:install to install again');
})->descriptions('Uninstall all PHP services from <fg=green>valet php:list</>');

/**
 * List all PHP versions and services
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
})->descriptions('List all PHP versions and services');

/**
 * Determine which PHP version the current working directory is using.
 * @param null|string $site Optionally, specify a site
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

	info("{$txt} {$which['site']} is using PHP {$which['phpVersion']}");
	info("The executable is located at: " . PhpCgi::getPhpPath($which['phpVersion']));

})->descriptions('Determine which PHP version the current working directory is using', [
	"site" => "Optionally, specify a site"
])->addUsage("php:which site2");

/**
 * Proxy PHP commands through to a site's PHP executable.
 *
 * This command block doesn't handle the command logic, the `valet` script in the project root does.
 * (The `valet` script is the actual command script that is called when `valet` is
 * ran in the terminal.)
 *
 * This command block is only to document the command within the CLI.
 */
$app->command('php:proxy phpCommand* [--site=]', function ($phpCommand, $site = null) {

	warning('It looks like you are running `cli/valet.php` directly; please use the `valet` script in the project root instead.');

})->setAliases(["php"])->descriptions("Proxy PHP commands through to a site's PHP executable", [
	'phpCommand' => "PHP command to run with the site's PHP executable",
	'--site' => 'Specify the site to use to get the PHP version.'
])->addUsage("php:proxy -v --site=site2")->addUsage("php -v --site=site2")

/**
 * Install Xdebug services for all PHP versions that are specified in `valet php:list`.
 * @param string $phpVersion Optionally, install a specific PHP version of Xdebug.
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
	$phps = PhpCgiXdebug::install($phpVersion);

	$phpVersion = $phpVersion ?: implode(", ", $phps);
	info("Installed Xdebug for PHP $phpVersion");

})->descriptions('Install all PHP Xdebug services from <fg=green>valet php:list</>', [
	"phpVersion" => "Optionally, install a specific PHP version of Xdebug"
])->addUsage("xdebug:install 7.4");

/**
 * Uninstall all Xdebug services.
 * @param string $phpVersion Optionally, uninstall a specific PHP version of Xdebug.
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
 * Get a calculation of the percentage of parity completion.
 */
$app->command('parity', function () {

	// Only get the released version instead of master, to make sure no commands
	// are added or removed to the macOS version to ensure this version of Valet 3.0
	// always has parity against the version in the URL. (ie. master can change.)
	//
	// Also, don't use a patch version, use the MAJOR.MINOR.0 format. eg. 4.3.0, 4.5.0
	Valet::parity("https://raw.githubusercontent.com/laravel/valet/v4.8.0/cli/app.php");

})->descriptions("Get a calculation of the percentage of parity completion.");

/**
 * Most commands are available only if Valet is installed.
 */
if (is_dir(VALET_HOME_PATH) && Nginx::isInstalled()) {
	/**
	 * Upgrade helper: ensure the tld config exists.
	 */
	if (empty(Configuration::read()['tld'])) {
		Configuration::writeBaseConfiguration();
	}

	/**
	 * Registers the current working directory to automatically serve sub-directories as sites
	 * and adds it to the paths configuration.
	 * @param string $path Optionally, specify a path
	 */
	$app->command('park [path]', function ($path = null) {
		Configuration::addPath($path ?: getcwd());

		info(($path === null ? 'This' : "The [{$path}]") . " directory has been registered to Valet and all sub-directories will be accessible as sites. To list the sites use <bg=magenta> valet parked </>");
	})->descriptions('Registers the current working directory to automatically serve sub-directories as sites', [
		"path" => "Optionally, specify a path"
	])->addUsage("park D:/sites");

	/**
	 * List all the current sites within parked paths.
	 */
	$app->command('parked', function () {
		$parked = Site::parked();

		if (count($parked) === 0) {
			return warning("No parked sites found.");
		}

		table(default_table_headers(), $parked->all());
	})->descriptions('List all the current sites within parked paths');

	/**
	 * Remove the current working directory from Valet's list of paths.
	 * @param string $path Optionally, specify a path
	 */
	$app->command('forget [path]', function ($path = null) {
		Configuration::removePath($path ?: getcwd());

		info(($path === null ? 'This' : "The [{$path}]") . " directory has been removed from Valet.");
	})->setAliases(["unpark"])->descriptions('Remove the current working directory from Valet\'s list of paths', [
		"path" => "Optionally, specify a path"
	]);

	/**
	 * Register the current working directory as a symbolic link.
	 *
	 * @param string $name Optionally specify a new name to be linked as.
	 * @param boolean $secure Optionally secure the site
	 * @param string $isolate Optionally isolate the site to a specified PHP version
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
	})->descriptions('Register the current working directory as a symbolic link', [
		'name' => 'Optionally specify a new name to be linked as',
		'--secure' => 'Optionally secure the site',
		'--isolate' => 'Optionally isolate the site to a specified PHP version'
	])->addUsage("link my_site_renamed")->addUsage("link my_site_renamed --isolate=7.4")->addUsage("link my_site_renamed --secure")->addUsage("link my_site_renamed --secure --isolate=7.4");

	/**
	 * List all of the registered symbolic links.
	 */
	$app->command('links', function () {
		$links = Site::links();

		if (count($links) === 0) {
			return warning("No linked sites found.");
		}

		// Recreate the list of links and remove the key of alias and aliasUrl,
		// as this is unnecessary for this command.
		$links = $links->map(function ($value, $key) {
			unset($value['alias']);
			unset($value['aliasUrl']);
			return $value;
		});

		table(['Site', 'Secured', 'PHP', 'URL', 'Path'], $links->all(), true);
	})->descriptions('List all of the registered symbolic links');

	/**
	 * Unlink the current working directory linked site
	 * @param string $name Optionally specify the linked site name
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

	})->descriptions('Unlink the current working directory linked site', [
		"name" => "Optionally specify the linked site name"
	])->addUsage("unlink my_site_renamed");

	/**
	 * Proxy a specified site to a specified host
	 * @param string $site The site to be proxied.
	 * Multiple sites can be proxied at the same time to 1 host. Separated by commas. eg. `site1,site2,site3`
	 * @param string $host The host to receive the site traffic
	 * @param boolean $secure Optionally, create a proxy with a trusted TLS certificate
	 */
	$app->command('proxy site host [--secure]', function ($site, $host, $secure) {
		Site::proxyCreate($site, $host, $secure);
		Nginx::restart();
	})->descriptions('Proxy a specified site to a specified host. Useful for docker, mailhog etc.', [
		"site" => "The site to be proxied. Multiple sites can be proxied by separating them with a comma.",
		"host" => "The host to receive the site traffic",
		"--secure" => "Optionally, secure with a trusted TLS certificate"
	])->addUsage("proxy site1 https://127.0.0.1:9200")->addUsage("proxy site1 https://127.0.0.1:9200 --secure")->addUsage("proxy site1,site2,site3 https://127.0.0.1:9200");

	/**
	 * List all the proxy sites.
	 */
	$app->command('proxies', function () {
		$proxies = Site::proxies();

		if (count($proxies) === 0) {
			return warning("No proxy sites found.");
		}

		table(['Site', 'Secured', 'URL', 'Host'], $proxies->all(), true);

	})->descriptions('List all the proxy sites');

	/**
	 * Remove a proxied site.
	 * @param string $site The site
	 */
	$app->command('unproxy site', function ($site) {
		Site::proxyDelete($site);
		Nginx::restart();
	})->descriptions('Remove a proxied site')->addUsage("unproxy site1");

	/**
	 * List all the parked, linked, and proxied sites.
	 */
	$app->command('sites', function () {
		$parked = Site::parked();
		$links = Site::links();
		$proxies = Site::proxies();

		if (count($parked) === 0 && count($links) === 0 && count($proxies) === 0) {
			return warning("No sites found within Valet.");
		}

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
			}
			else {
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
	})->descriptions('List all the parked, linked, and proxied sites');

	/**
	 * Secure the current working directory with a trusted TLS certificate.
	 * @param string $site Optionally specify the site.
	 */
	$app->command('secure [site]', function ($site = null) {
		$url = Site::getSiteURL($site);

		Site::secure($url);

		Nginx::restart();

		info('The [' . $url . '] site has been secured with a fresh TLS certificate and will now be served over HTTPS.');

	})->descriptions('Secure the current working directory with a trusted TLS certificate', [
		"site" => "Optionally specify the site"
	])->addUsage("secure site1");

	/**
	 * List all secured sites.
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

	})->descriptions('List all secured sites');

	/**
	 * Unsecure the current working directory
	 * @param string $site Optionally specify the site.
	 * @param boolean $all Optionally unsecure all secured sites
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

		info('The [' . $url . '] site has been unsecured and will now be served over HTTP.');

	})->descriptions('Unsecure the current working directory', [
		"site" => "Optionally specify the site.",
		"--all" => "Optionally unsecure all secured sites"
	])->addUsage("unsecure mySite")->addUsage("unsecure --all");

	/**
	 * Change the default PHP version used by Valet.
	 *
	 * @param string $phpVersion The PHP version eg. 8.1.8, or an alias eg. 8.1 to be set as the default
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

	})->descriptions('Change the default PHP version used by Valet.', [
		'phpVersion' => 'The PHP version you want to use, e.g 8.1, 8.1.8'
	])->addUsage("use 8.1")->addUsage("use 8.1.8");

	/**
	 * Isolate the current working directory to specific PHP version.
	 *
	 * @param string $phpVersion The PHP version you want to use, eg. "7.4.33"; or an alias, eg. "7.4"
	 * @param array $site Optionally specify the site.
	 * To specify multiple sites, you use it like:
	 * `--site=my-project --site=another-site`
	 */
	$app->command('isolate [phpVersion] [--site=]*', function ($phpVersion, $site = []) {
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
	})->descriptions('Isolate the current working directory to a specific PHP version', [
		'phpVersion' => 'The PHP version you want to use; e.g 7.4, 7.4.33',
		'--site' => 'Optionally specify the site.'
	])->addUsage("isolate 7.4")->addUsage("isolate 7.4 --site=mySite")->addUsage("isolate 7.4.33 --site=mySite --site=site2");

	/**
	 * List all isolated sites.
	 */
	$app->command('isolated', function ($output) {

		$isolated = Site::isolated();

		if (count($isolated) === 0) {
			info("There are no isolated sites.");
			return;
		}

		table(["Site", "PHP"], $isolated->all(), true);

	})->descriptions('List all isolated sites.');

	/**
	 * Remove [unisolate] the current working directory's site
	 * @param string $site Optionally specify the site
	 * @param boolean $all Optionally unisolates all isolated sites
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

	})->descriptions('Remove [unisolate] the current working directory\'s site', [
		'--site' => 'Optionally specify the site',
		'--all' => 'Optionally remove all isolated sites'
	])->addUsage("unisolate --site=mySite")->addUsage("unisolate --all");

	/**
	 * Sharing - ngrok
	 */

	/**
	 * Set the ngrok authtoken.
	 * @param string $token Your personal ngrok authtoken
	 */
	$app->command('set-ngrok-token [token]', function ($token = null) {
		if ($token === null) {
			warning("Please provide your ngrok authtoken.");
			return;
		}

		Ngrok::run("authtoken $token " . Ngrok::getNgrokConfig());

	})->setAliases(["auth"])->descriptions('Set the ngrok auth token', [
		"token" => "Your personal ngrok authtoken"
	])->addUsage("set-ngrok-token 123abc")->addUsage("auth 123abc");

	/**
	 * Share the current working directory site with a publically accessible URL.
	 *
	 * @param string $site Optionally, specify a site.
	 *
	 * @param string|null $options Optionally, specify ngrok options/flags of its `http` command to pass to ngrok.
	 * ---
	 * Pass the option name without the `--` prefix (so Valet doesn't get confused with its own options); eg. `--options domain=example.com`.
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
	 * @param bool $debug Allow error messages to output to the current terminal
	 */
	$app->command('share [site] [-o|--options=] [--debug]', function ($input, $site = null, $options = null, $debug = null) {

		// Send an error if the option shortcut has a =
		if (str_contains($input, "-o=")) {
			return error("Option shortcuts cannot have a <bg=magenta> = </> immediately after them.\nPlease use a space to separate it from the value: <bg=magenta> valet share $site -o " . preg_replace("/=/", "", $options, 1) . " </>");
		}

		$url = ($site ?: strtolower(Site::host(getcwd()))) . '.' . Configuration::read()['tld'];

		$options = $options != null ? explode("//", $options) : [];

		Ngrok::start($url, Site::port($url), $debug, $options);

	})->descriptions('Share the current working directory site with a publically accessible URL', [
		"site" => "Optionally, the site name",
		"--options" => "Optionally, specify ngrok options/flags of its `http` command to pass to ngrok, without the leading <fg=green>--</>. Multiple options must be separated by double slashes <fg=green>//</>."
	])->addUsage("share mysite")->addUsage("share mysite --options domain=example.com//region=eu")->addUsage("share -o domain=example.com");

	// TODO: Share-tool for Expose (https://expose.dev/)
	// and 2 open-source clients like localtunnel (https://github.com/localtunnel/localtunnel)
	// https://boringproxy.io/
	// https://github.com/anderspitman/awesome-tunneling
	// https://github.com/robbie-cahill/tunnelmole-client

	/**
	 * Get and copy the public URL of the current working directory site
	 * that is currently being shared
	 * @param string|null $site Optionally, specify a site
	 */
	$app->command('fetch-share-url [site]', function ($site = null) {
		$site = $site ?: Site::host(getcwd()) . '.' . Configuration::read()['tld'];

		$url = Ngrok::currentTunnelUrl($site);
		info("The public URL for $site is: <fg=blue>$url</>");
		info("It has been copied to your clipboard.");

	})->setAliases(["url"])->descriptions('Get and copy the public URL of the current working directory site that is currently being shared', [
		"site" => "Optionally, specify a site"
	])->addUsage("fetch-share-url site1")->addUsage("url site1");

	/**
	 * Run ngrok commands.
	 *
	 * @param array $commands The ngrok commands plus it's argument's values.
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
		"commands" => "The ngrok command and its argument's values, separated by spaces.",
		"--options" => "Specify ngrok options/flags without the leading <fg=green>--</>. Multiple options must be separated by double slashes <fg=green>//</>."
	])->addUsage("ngrok config add-authtoken [token] --options config=C:/ngrok.yml")->addUsage("ngrok config add-authtoken [token] -o config=C:/ngrok.yml");

	/**
	 * Starts Valet's services
	 *
	 * Proxies through to the `restart` command.
	 *
	 * @param string $service Optionally, specify a particular service to start.
	 * [acrylic, nginx, php, xdebug]
	 */
	$app->command('start [service]', function ($service) {
		$this->runCommand('restart ' . $service . ' --dev-txt=started');
	})->descriptions('Starts Valet\'s services', [
		"service" => "Optionally, specify a particular service to start [acrylic, nginx, php, xdebug]"
	])->addUsage("start acrylic")->addUsage("start nginx")->addUsage("start php")->addUsage("start xdebug");

	/**
	 * Restarts Valet's services
	 * @param string $service Optionally, specify a particular service to start.
	 * [acrylic, nginx, php, xdebug]
	 * @param string $devTxt Adds specific text to the output. INTERNAL USE ONLY.
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

			case 'xdebug':
				PhpCgiXdebug::restart();

				return info("PHP Xdebug has been $devTxt.");
		}

		return warning(sprintf('Invalid Valet service name [%s]', $service));

	})->descriptions('Restarts Valet\'s services', [
		"service" => "Optionally, specify a particular service to restart [acrylic, nginx, php, xdebug]",
		"--dev-txt" => "INTERNAL USE ONLY"
	])->addUsage("restart acrylic")->addUsage("restart nginx")->addUsage("restart php")->addUsage("restart xdebug");

	/**
	 * Stops Valet's services
	 * @param string $service Optionally, specify a particular service to stop.
	 * [acrylic, nginx, php, xdebug]
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

	})->descriptions('Stops Valet\'s services', [
		"service" => "Optionally, specify a particular service to stop [acrylic, nginx, php, xdebug]"
	])->addUsage("stop acrylic")->addUsage("stop nginx")->addUsage("stop php")->addUsage("stop xdebug");

	/**
	 * Get the TLD currently being used by Valet.
	 * @param string $tld Optionally, set a new TLD.
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

	})->descriptions('Get the TLD currently being used by Valet', [
		"tld" => "Optionally, set a new TLD"
	])->addUsage("tld code");

	/**
	 * Determine which Valet driver the current working directory is using
	 */
	$app->command('which', function () {
		require __DIR__ . '/drivers/require.php';

		$driver = ValetDriver::assign(getcwd(), basename(getcwd()), '/');

		if ($driver) {
			info('This site is served by [' . get_class($driver) . '].');
		}
		else {
			warning('Valet could not determine which driver to use for this site.');
		}
	})->descriptions('Determine which Valet driver the current working directory is using');

	/**
	 * List all of the paths registered with Valet.
	 */
	$app->command('paths', function () {
		$paths = Configuration::read()['paths'];

		if (count($paths) > 0) {
			$newPaths = [];
			foreach ($paths as $path) {
				array_push($newPaths, [$path]);
			}

			table(["Paths"], $newPaths, true);
		}
		else {
			info('No paths have been registered.');
		}
	})->descriptions('List all of the paths registered with Valet');

	/**
	 * Open the current working directory site in the browser
	 * @param string $site Optionally, specify a site
	 */
	$app->command('open [site]', function ($site = null) {
		$url = 'http://' . ($site ?: Site::host(getcwd())) . '.' . Configuration::read()['tld'];
		CommandLine::passthru("start $url");

	})->descriptions('Open the current working directory site in the browser', [
		"site" => "Optionally, specify a site"
	])->addUsage("open site1");

	/**
	 * Determine if this is the latest version/release of Valet.
	 */
	$app->command('on-latest-version', function () use ($version) {
		if (Valet::onLatestVersion($version)) {
			info('Yes');
		}
		else {
			warning(sprintf('Your version of Valet Windows (%s) is not the latest version available.', $version));
			output("You can use <fg=cyan>composer global update</> to update to the latest release (after uninstalling Valet first).\nPlease read the documentation and the Changelog for information on possible breaking changes.");
		}
	})->setAliases(["latest"])->descriptions('Determine if this is the latest version/release of Valet');

	/**
	 * View and follow a log file.
	 * @param string $key The name of the log
	 * @param string $lines The number of lines to view
	 * @param boolean $follow Follow real time streaming output of the changing file
	 */
	$app->command('log [key] [-l|--lines=] [-f|--follow]', function ($key, $lines, $follow) {
		$defaultLogs = [
			'nginx' => VALET_HOME_PATH . '/Log/nginx-error.log',
			'nginxservice.err' => VALET_HOME_PATH . '/Log/nginxservice.err.log',
			'nginxservice.out' => VALET_HOME_PATH . '/Log/nginxservice.out.log',
			'nginxservice.wrapper' => VALET_HOME_PATH . '/Log/nginxservice.wrapper.log',
			'phpcgiservice.err' => VALET_HOME_PATH . '/Log/phpcgiservice.err.log',
			'phpcgiservice.out' => VALET_HOME_PATH . '/Log/phpcgiservice.out.log',
			'phpcgiservice.wrapper' => VALET_HOME_PATH . '/Log/phpcgiservice.wrapper.log'
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
				null
			]));

			$logs = collect($logs)->map(function ($file, $key) {
				return [$key, $file];
			})->toArray();

			table(['Key', 'File'], $logs, true);

			info(implode(PHP_EOL, [
				null,
				'Tip: Set custom logs by adding a "logs" key/file object',
				'to your "' . Configuration::path() . '" file.'
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
		if ((int) $lines) {
			$options[] = '-Tail ' . (int) $lines;
		}
		if ($follow) {
			$options[] = '-Wait';
		}

		$options = implode(' ', $options);

		CommandLine::powershell("cat -Path $file $options", null, true);

	})->descriptions('View and follow a log file', [
		"key" => "The name of the log",
		"--lines" => "The number of lines to view.",
		"--follow" => "Follow real time streaming output of the changing file"
	])->addUsage("log nginx --lines=3 --follow")->addUsage("log nginx -l 3 -f");

	/**
	 * List the installed Valet services.
	 */
	$app->command('services', function () {
		info("Checking the Valet services...");

		$services = Valet::services();
		output("\n");

		table(['Service', 'Windows Name', 'Status'], $services, true);
		info('Use <bg=magenta> start </> <bg=magenta> stop </> or <bg=magenta> restart </> commands to change the status, eg. <bg=magenta> valet restart nginx </>');
	})->descriptions('List the installed Valet services.');

	/**
	 * Determine directory-listing behaviour. Default is off, which means a 404 will display.
	 * @param string $status Optionally, switch directory listing [on, off]
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

	})->descriptions('Determine directory-listing behaviour. Default is off, which means a 404 will display.', [
		'status' => "<fg=green>[off = default]</> will show a 404 page \n <fg=green>[on]</> will display a listing if project folder exists but requested URI not found"
	]);

	/**
	 * Uninstalls Valet's services
	 * @param bool $force Optionally force an uninstall without confirmation.
	 * @param bool $purgeConfig Optionally purge and remove all Valet configs
	 */
	$app->command('uninstall [--force] [-p|--purge-config]', function ($input, $output, $force, $purgeConfig) {

		$helper = $this->getHelperSet()->get('question');

		if (!$force) {
			$xdebug = !PhpCgiXdebug::installed() ? "" : " PHP CGI Xdebug,";
			$txt = !$purgeConfig ? "." : ", and all Valet configs and logs.";
			warning("YOU ARE ABOUT TO UNINSTALL Nginx, PHP-CGI,$xdebug Acrylic DNS and Ansicon$txt");
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
			if (count(Site::secured()) > 0) {
				info("\n\nRemoving certificates for all secured sites...");
				Site::unsecureAll(true);
				sleep(1);
			}
		}
		else {
			Site::untrustCertificates();
		}
		$progressBar->clear();

		// INFO: Uninstallation...

		$maxItems = $purgeConfig ? $maxItems + 2 : $maxItems + 1;
		$progressBar = progressbar($maxItems, "Uninstalling");
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
			$progressBar->setMessage("Configuration", "placeholder");
			$progressBar->advance();
			Configuration::uninstall();
			sleep(1);
		}

		$progressBar->finish();
		$txt = !$purgeConfig ? "." : ", and purged all configs.";
		info("\n\nValet has been uninstalled from your system$txt");

		if (!$purgeConfig) {
			info("If you wanted to update Composer, you are now safe to do so: <bg=magenta> composer global update </>");
			output("\nOr");
		}

		output(
			"\nRemove the Composer dependency with: <bg=magenta> composer global remove ycodetech/valet-windows </>" .
			($purgeConfig ? '' : "\nDelete the config files from: <info>~/.config/valet</info>") .
			"\nDelete PHP from: <info>C:/php</info>"
		);
	})->descriptions('Uninstalls Valet\'s services', [
		'--force' => 'Optionally force an uninstall without confirmation.',
		"--purge-config" => "Optionally purge and remove all Valet configs."
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