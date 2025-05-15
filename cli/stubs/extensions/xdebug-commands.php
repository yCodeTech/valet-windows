<?php

namespace Valet;

use Valet\PhpCgiXdebug;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use function Valet\info;
use function Valet\info_dump;
use function Valet\warning;
use function Valet\error;

// Resolve the PhpCgiXdebug class as an object and assign it to the `PHP_XDEBUG` global
// constant variable to be able to use it in the commands.
define("PHP_XDEBUG", resolve(PhpCgiXdebug::class));
/**
 * Install Xdebug services for all PHP versions that are specified in `valet php:list`.
 * @param string $phpVersion Optionally, install a specific PHP version of Xdebug.
 */
$app->command('xdebug:install [phpVersion]', function ($input, $output, $phpVersion = null) {

	$txt = $phpVersion === null ? "" : " for PHP $phpVersion";

	if (PHP_XDEBUG->installed($phpVersion)) {
		info("Xdebug{$txt} is already installed.");

		$helper = $this->getHelperSet()->get('question');
		$question = new ConfirmationQuestion("<fg=yellow>Do you want to reinstall it? yes/no</>\n", false);

		if (!$helper->ask($input, $output, $question)) {
			return warning('Install aborted.');
		}
	}

	info('Installing Xdebug services...');
	$phps = PHP_XDEBUG->install($phpVersion);

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
	if (!PHP_XDEBUG->installed($phpVersion)) {
		warning("Xdebug for PHP $phpVersion is not installed.");
		return;
	}

	PHP_XDEBUG->uninstall($phpVersion);

	info('Xdebug services uninstalled. Run <bg=gray>xdebug:install [phpVersion]</> to install again');

})->descriptions('Uninstall all PHP Xdebug services from <fg=green>valet php:list</>', [
	"phpVersion" => "Optionally, uninstall a specific PHP version of Xdebug"
]);