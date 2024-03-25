# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/yCodeTech/valet-windows/tree/master)

## [3.1.1](https://github.com/yCodeTech/valet-windows/tree/v3.1.1) - 2024-03-25

### Added

- **Aesthetic only** - Added the logo as an ASCII art to the namespace command `valet`; and the `valet list` command, via extending Silly's Application class.

### Changed

- Updated dependencies for Laravel installer to add support for Laravel 11, (PR by @
  onecentlin in https://github.com/yCodeTech/valet-windows/pull/10).

## [3.1.0](https://github.com/yCodeTech/valet-windows/tree/v3.1.0) - 2024-02-28

### Added

- Added new `parity` command to get a calculation of the percentage of parity completion against Laravel Valet for macOS.

### Changed

- Updated dependencies for Laravel installer to add support for Laravel 10, (PR by @hemant-kr-meena in https://github.com/yCodeTech/valet-windows/pull/8).

### Fixed

- Fixed https://github.com/yCodeTech/valet-windows/issues/6 PHP 8+ deprecation notice for required parameters declared after optional parameters.

  The notice would only show when the PHP ini `error_reporting` setting wasn't ignoring deprecation notices, and all reports are enabled, ie. `E_ALL`. This notice would then be displayed in the terminal on every `valet` command. Though it doesn't impact valet's functionality, it could potentially be broken in future PHP versions.

  Fixed by:

  - Adding a default `null` value to the `$debug` parameter for the optional `--debug` option of the `share` command.

  - Removing the default `null` value from the `$key` parameter of the optional `key` argument of the `log` command. Because the key is used to specify the name of a specific log and omitting the argument will make valet list all the log file names, it doesn't need to have a default value.

### Deprecated

- The `install` confirmation question of outdated cretueusebiu package. Doesn't affect valet functionality.

## [3.0.0](https://github.com/yCodeTech/valet-windows/tree/v3.0.0) - 2023-09-21

### Added

- Added support for multiple PHP services, (Feature PR by @iamroi in https://github.com/cretueusebiu/valet-windows/pull/195).

  - Enables the use of the previously disabled `valet use` command, to switch the default PHP version used by Valet.
  - Adds new commands `php:add`, `php:remove`, `php:install`, `php:uninstall`, `php:list`, `xdebug:install`, `xdebug:uninstall`.

- Added PHP version to the `links` command output (Patch 1 PR by @damsfx in https://github.com/iamroi/valet-windows/pull/1).

- Added PHP version to the `parked` command output, improvements to the Patch 1 above and a new command `php:which` (Patch 2 by @yCodeTech in https://github.com/yCodeTech/valet-windows/pull/1)

  - Adds `default` or `isolated` to the PHP version output, with the latter being coloured green for emphasis in both `parked` and `links` commands. This acts as a 2 in 1, showing the PHP version, and determines whether the site is isolated.

  - Adds `Alias` and `Alias URL` to the `parked` command output. If the parked site also has a symbolic link, it's linked name (aka alias) and alias URL will be outputted in the table.

  - Adds new `php:which` command to determine which PHP version the current working directory or a specified site is using.

  - Changes the output table to vertical for easier reading, using Symfony's `setVertical` method (only works when Valet is installed on PHP 8.1).

- Added new `isolate` and `unisolate` commands. Isolates the current working directory or a specified site (also specify multiple sites) to a specific PHP version, and removes an isolated site (also unisolate all sites), respectively.

- Added `version_alias` to the `addPhp` function in the `Configuration.php` file, creating an alias name for the full PHP version, which is then written to the user's Valet `config.json`. A full PHP version of 8.1.8 will have the alias of 8.1, the alias can then be used in commands.

- Added PHP's `krsort` function to the `addPhp` function in the `Configuration.php` file, so that the PHP array in the user's Valet `config.json` is written in a natural decending order that adheres to decimals.

  Natural decending order example:

  ```
  8.1.18

  8.1.8

  7.4.33
  ```

  This means that when two different patch versions of the same SemVer MAJOR.MINOR version of PHP is added like 8.1.8 and 8.1.18; and then the `use` command is ran with the alias version like 8.1, then the default will be set to the most recent version of that alias. In this example, it would be 8.1.18.

- Added `isolated` command to list all the isolated sites.
- Added `Version Alias` to the table output of `php:list`.
- Added `--isolate` option to the `link` command to optionally isolate the site whilst making it a symbolic link.
- Added the ability to unsecure a site when a secured linked site is unlinked before removing the site's conf to ensure it's removed from Windows internal certificate store.
- Added the ability to unisolate a site when an isolated linked site is unlinked, to ensure it removes it properly.
- Added `secured` command to list all the secured sites.
- Added Valet Root CA generation and sign TLS certificates with the CA (PR by @shawkuro in https://github.com/cretueusebiu/valet-windows/pull/179).
- Added row separators for horizontal tables.
- Added `sites` command to list all sites in parked, links and proxies.
- Added `set-ngrok-token` to set ngrok authtoken and added a command alias for it: `auth`.
- Added `--debug` option to the `share` command to prevent the opening of a new CMD window, and allow error messages to be displayed from ngrok for easier debugging. This is needed because ngrok may fail silently by opening a new CMD window and quickly closes it if it encounters an error, so no errors are outputted.
- Added an `--options` option to the `share` command to pass any ngrok options/flags for the ngrok `http` command, which Valet will pass through to ngrok. Also added a shortcut `-o`. See the [docs](https://github.com/yCodeTech/valet-windows/blob/master/README.md#notes-for-all---options) for information on how this works.
- Added `sudo` command and [gsudo](https://github.com/gerardog/gsudo) files. The new command is to `passthru` Valet commands to the commandline that need elevated privileges by using gsudo. gsudo is a `sudo` equivalent for Windows, it requires only 1 UAC popup to enable the elevation and then all commands will be executed as the system instead of having multiple UACs opening.

  Also added an error message for if `valet sudo sudo` is ran, because you can't sudo the sudo command.

- Added `valetBinPath` helper function to find the Valet bin path, and updated all the code to use it.
- Added a check to see if a site is isolated before unisolating it.
- Added command example usages to display in the console when using `--help`.
- Added a progressbar UI to `services` function, and `install`, `uninstall`, `restart`, `stop` commands to improve the UX.
- Added `error` output to the `getPhpByVersion` function to cut down on duplicate `error` code that relates to the function.
- Added a sleep for 0.3s (300000 microseconds) in between the `uninstall` warning and the question to allow the warning be output before the question is outputted. And simplified the if statements.
- Added a command alias of `unpark` to the `forget` command.
- Added a composer conflict for the old unmaintained cretueusebiu/valet-windows version, just so composer can't install this 3.0 version alongside it.
- Added parity related additions for proxying.
  - Added `--secure` option to `proxy` command.
  - Updated the proxy stub to be the unsecure proxy stub as default.
  - Changed the `proxyCreate` in `Site` class to accommodate for the new `--secure` option.
  - Added new `secure.proxy.valet.conf` stub for the secure proxy.
  - Changed `resecureForNewTld` to check for the new `secure.proxy` stub to ensure it keeps it secured when reinstalling Valet.
  - Added support for proxying multiple sites at once by separating them with commas, in both `proxy` and `unproxy` commands.

### Changed

- Changed package namespace to `yCodeTech`.
- Changed capitalisation from `valet` to `Valet` in various outputs and code comments where the don't refer to the commands.
- Changed the output table to vertical for easier reading on those longer columns, with an optional argument to draw the table horizonally.
- Renamed the `usePhp` function to `isolate` in `Site.php` file to reflect it's command name.
- Updated ngrok to the latest version of 3.3.1
- Moved Valet's version variable out and into it's own separate file for ease.
- Changed various function return types.
- Changed output tables `SSL` columns to `Secure` for easier understanding.
- Changed `error` helper function to throw an exception when specified to do so, and add more meaning to the error output by constructing the error message from the `Exception` class. This is because sometimes the exception doesn't output the exact error or file names needed in order to debug. So reconstructing the error from the class methods should fix it.

- Changed the table style to `box` which outputs solid borders instead of using dashes.
- Changed the name of the `starts_with` and `ends_with` helper functions to `str_starts_with` and `str_ends_with` respectively to reflect the PHP 8+ functions.
- Updated various output texts.
- Changed the way the `secure` command was getting the current working directory to use the `getSiteURL` function instead.
- Changed various `warning`s to `error`s.
- Changed `domain` text and variables to `site` to properly reference the `site`.
- Changed text to use the proper capitalisation of `Xdebug`.
- Changed the 404 template to be more visually appealing by adding the Valet 3 logo - the logo also acts as a clarification that if the 404 happens we know it's something to do with Valet and nothing else.
- Changed Xdebug's installation behaviour to no longer install automatically, without specific flag being present. This is because Xdebug is only a PHP debugging service, so if it's not used, then it's wasting a bit of resources.

  - Added an `--xdebug` option to the commands `php:add` and `install` to optionally install Xdebug while installing the PHP or installing Valet respectively.
  - Added an optional `phpVersion` argument to the commands `xdebug:install` and `xdebug:uninstall` to install or uninstall Xdebug for a specfic PHP version. If installing and the version is already installed, ask the user if they want to reinstall it.
  - Added function to check if a supplied PHP version is the alias or not, and a function to get the full PHP version by the alias. Used in `PhpCgiXdebug` and `PhpCgi` files.
  - Added a function to check if Xdebug of a specfic PHP version is installed, or if a version isn't supplied then check if any version is installed for the PHP installed in Valet. Used for many of the commands to uninstall if it is installed.
  - Added the service ID to `WinSwFactory` to allow `WinSW` functions get the and use the full ID in order to fully check if it's installed. Used in `PhpCgiXdebug`, `PhpCgi`, and `Nginx` files.
  - Changed Xdebug service name.
  - Changed the powershell cli command of the `installed` function of `WinSW` file to use the newly added service ID instead of the name. And removed the now unnecessary extra code.
  - Changed various warning outputs to errors.
  - Removed the `getPhpCgiName` function from `PhpCgiXdebug` class because the function exists in the parent class and should be used instead, thus removing duplicate code.
  - Fixed `xdebug:install`, previously, when no PHP version is passed, the command will reinstall Xdebug even if it's already installed without asking the user. Fixed so that it asks just as it does when a PHP version was passed. Changed the output text accordingly.
  - Removed the redundant `isInstalledService` function in favour of using the `installed` function of `WinSW`, as it does exactly the same, thus removing duplicate code.

- Changed the path argument of `php:add` to required rather than optional (removed the square brackets).
- Changed the `services` function in the `Valet.php` file to output `not installed` instead of `missing` for the Xdebug services, because it's not essential for Valet to run, so it shouldn't be labelled as missing.
- Replaced the `DIRECTORY_SEPARATORs` and `\\` for `/` in all paths and using `str_replace` to replace `\\` into `/`, so there isn't any weird paths like `C:\\sites/mysite`.
- Overhauled the `diagnose` command.

  - Changed `Diagnose` class to use the `progressbar` helper function instead of initiating Symfony's `ProgressBar` class separately.

  - Removed commands not applicable for Windows.

  - Added various other commands that will be necessary to debug.

  - Added a `COMPOSER_GLOBAL_PATH` constant to the helpers and a `getComposerGlobalPath` function to the `Valet` class to be able to get and use the global path of composer in the commands.

  - Fixed the output for `composer global diagnose`, where it would only output 1 line of an info and no diagnostics. Fixed by outputting it to a file first and then reading the file before removing it.

  - Fixed the output for `composer global outdated` to format as a HTML table in the output for copy. Additionally, made the terminal output more human readable depending on the command option used.

  - Removed the unnecessary `runCommand` function, and used the `powershell` function of the `CommandLine` class instead. Powershell is used because it has native support for the `cat` function, which is the alias of `Get-Content` for getting file contents.

  - Fixed the ability to copy to clipboard.

  - Various changes to output for human readability or because the raw output wasn't good enough or had quirks.

- Changed `log` command to use `cat` alias of the Powershell's `Get-Content` command instead of the `tail` command which only works in Git Bash.

  - Also changed the options to that of Powershell's variants `-Tail` for how many lines and `-Wait` for following real time output.

  - Swapped around the Valet command's options, and changed various descriptions.

  - Changed the `runCommand` `CommandLine` function to allow real time output. The `setTimeout` is set to 0 to allow it to run for what should be "forever". Though this can't be tested for obvious reasons.

  - Added a boolean param to all the other commandline functions that utilise the aforementioned function, so they can pass along and use the real time output if/when needed.

### Removed

- Removed the deprecated PHP PowerShell files.
- Removed unnecessary/redundant/duplicate code.
- Removed the `--site` option from the `use` command that was added in https://github.com/cretueusebiu/valet-windows/pull/195, in favour of using the `isolate` command.
- Removed the deprecated `getLinks` function in the `Site.php` file.
- Removed the deprecated and unnecessary `publishParkedNginxConf`, `runOrDie`, `should_be_sudo`, `quietly`, `quietlyAsUser` functions.
- Removed the unsupported `trust` command.
- Removed the hardcoded ngrok options from the `share` command in favour of the new `--options` option.
- Removed the `echo` from the `trustCa` function that was in the PR code from https://github.com/cretueusebiu/valet-windows/pull/179
- Removed various outputs to fully streamline the progressbar UI and prevent multiple progressbars in the output because of multiple infos interrupting it.
- Removed the ability to download PHP via an internal PowerShell script (`php.ps1`), because keeping it updated with the current versions of PHP and deprecating it's ancestor versions is impractical. Deleted the file and all related PHP `.ini`s.
- Removed and deleted the unused and outdated tests, `.dockerignore`, `phpunit.xml` config files, and the related composer dependencies and scripts.
- Removed the deprecated and unused legacy home path code, inline with the Mac version.
- Removed the obsolete `domain` alias for `tld` command.

### Fixed

- Fixed securing sites with an SSL/TLS certificate, both normally and when proxies are added by adding the localhost IP address to the Nginx conf listen directives. (PR by @RohanSakhale in https://github.com/cretueusebiu/valet-windows/pull/208).
- Fixed a bug where sometimes the link won't be unlinked under certain conditions. In accordance with [official PHP guidelines of the `unlink()` function](https://www.php.net/manual/en/function.unlink.php), the function `rmdir()` fixes this issue to remove symlink directories.

  ###### From PHP `unlink()` docs:

  > If the file is a symlink, the symlink will be deleted. On Windows, to delete a symlink to a directory, rmdir() has to be used instead.

- Fixed Nginx `lint` function to properly check the confs for errors.
- Fixed filesystem `copy` function to use the `@` operator to suppress pr-error messages that occur in PHP internally. And added an inline `error` function if `copy` fails. We do this, so that we can construct proper meaningful error output for debugging.
- Partial fix for a possible bug where if a site is using a framework that sets a site URL environment variable in a `.env` file such as the `WP_HOME` for Laravel Bedrock, then when trying to request the site and the TLD is different from the one set in Valet, then the site automatically redirects to use the URL from the environment variable. This ends in Valet returning a 404 error, because as far as Valet is concerned it's not valid site. This then results in the response being cached by the browser and keeps requesting the cached version of the site even if the TLD has been changed to match.

  Example: `WP_HOME='http://mySite.test'`, Valet is set to use the `dev` TLD and gets a request to `http://mySite.dev`, the site will auto redirect to `http://mySite.test`. 404 Not Found error appears. The response from `http://mySite.test` is cached by browsers. Valet is changed to use `test` TLD, and gets a request for `http://mySite.test`. The previously cached error response is served.

  This partial fix adds `no cache` headers to Nginx configuration files to try and prevent the browsers caching sites at all.

- Fixed `services` command to correctly loop through and check all PHP services.
- Fixed `fetch-share-url` command by:

  - Replacing the outdated `nategood/httpful` composer dependency with `guzzlehttp/guzzle` for REST API requests to get the current ngrok tunnel public URL, and copy it to the clipboard.
  - Changing the `findHttpTunnelUrl` function to use array bracket notation.
  - Changing the `domain` argument to `site` to properly reference the site without getting confused with the ngrok `--domain`.
  - Added a command alias: `url`.

- Fixed `on-latest-version` command to use Guzzle and added a new composer dependency `composer/ca-bundle` to find and use the TLS CA bundle in order to verify the TLS/SSL certificate of the requesting website/API. Otherwise, Guzzle spits out a cURL error. (Thanks to this [StackOverflow Answer](https://stackoverflow.com/a/53823135/2358222).)

  Also added a command alias: `latest`.

- Fixed `ngrok` command to accept options/flags, using the new `--options` option. See the [docs](https://github.com/yCodeTech/valet-windows/blob/master/README.md#notes-for-all---options) for information on how this works.
- Fixed `php:remove` command to enable it to remove PHP by specifying it's version; by adding a `phpVersion` argument and changing the `path` argument to an option (`--path`), making `phpVersion` the main way to remove instead.
- Fixed `start` command by removing the whole functionality and utilise Silly's `runCommand` to run the `restart` command instead, so they're effectively sharing the same function. This is because it was unnecessary duplicated code.
- Fixed lack of output colouring when using PHP `passthru` function by adding a 3rd party binary, [Ansicon](https://github.com/adoxa/ansicon), along with a new class with `install`/`uninstall` functions and added the function calls to the Valet `install`/`uninstall` commands respectively.

  For whatever reason, the `passthru` function loses the output colourings, therefore the visual meaning is lost. What Ansicon does is injects code into the active and new terminals to ensure ANSI escape sequences (that are interpreted from the HTML-like tags `<fg=red></>`) are correctly rendered.

- Fixed a bug where a non-secured isolated site's server port was being replaced with the PHP port and was being served with the default server instead of the isolated server.

  So instead of the usual port 80: `127.0.0.1:80` in the site's isolated config file, nginx was actually listening for the site on port 9002: `127.0.0.1:9002`, which is the port where PHP 7.4 was configured to be hosted. This confused nginx because it couldn't find port 80 in the site's config file, so it used the default server instead, in which the default PHP version was set as 8.1 on the port 9001, and thus serving the site under the wrong PHP version.

  Fixed by removing the `preg_replace` in the `replacePhpVersionInSiteConf` function of the `Site` class. (This doesn't affect the PHP isolation and still works as intended.)

- Fixed an oversight while changing the TLD by allowing isolated sites TLD to be changed. Previously, if there is an isolated site, changing the TLD wouldn't change the isolated site's conf file, thus leaving it as the old TLD. This fix adds a `reisolateForNewTld` function to unisolate the old TLD site and reisolate the new TLD site. Also works for sites that are both isolated and secured.

- Fixed `unlink` command to properly get the site from the current working directory if no `name` was supplied, by using the previously `private`, now `public` `getLinkNameByCurrentDir()` function. Also changed the error message in the latter function to include the multiple linked names.

- Fixed `stop` function of the `PhpCgi` class to only `stop` the PHP CGI or PHP CGI Xdebug if it's installed.

  This prevents errors occurring when `uninstall`ing Valet. It would try to `stop` Xdebug services for all PHP versions available to Valet, even though all Xdebug services may not be installed.

- Fixed `unsecure --all` to `exit` the script if there are no sites to unsecure, otherwise nginx would still run afterwards, which is not needed.

- Fixed `uninstall` command to only unsecure sites if they are any secured sites. Also prevented the `unsecureAll` function from exiting the script if the call came from `uninstall`. Also added an option shortcut `-p` for `--purge-config`.

- Fixed `install` command to detect if Valet is already installed. Checks the services to see if they are running, if they are, Valet will ask a question whether to reinstall Valet or not. Also changed `services` function (which this fix uses) to disable the progressbar when it's called from `install`.

- Fixed the Xdebug `failed to restart` error in the `restart` function of the `PhpCgi` class (of which `PhpCgiXdebug` class shares) by adding a check to see if the Xdebug service is installed, it will only restart the service if it's installed.

  This is because previously, if there is a version of Xdebug installed and other versions of PHP didn't have their corresponding Xdebug installed, and the `restart` function is ran, Valet would then spit out an error in complaint of the Xdebug PHP version failing to restart, because it's not installed.

- Fixed the `default` PHP configuration on `install`, by allowing Valet to only set the default PHP if they key doesn't exist or if it's `null`.

  This is because previously, upon installing Valet, if the config file already exists with a default PHP set, Valet will always reset the default no matter what, even if Valet is set to use a version other than the version it finds from the `where php` CMD command, which gets it from Windows PATH (eg. default in config: 7.4.33, Valet finds: 8.1.8; Valet resets the default back to 8.1.8).

  - Also fixed a bug in relation to this flaw... When adding the default PHP, Valet would sometimes fail to get the PHP path because of the inconsistent capitalisation of the drive letter in the PHP path. If the path's drive letter is a lowercase, it would fail because of the strict comparison between the former and Valet's retrieval of the uppercase letter from `where php` (eg. config: c:/php/8.1; Valet retrieves: C:/php/8.1).

  Therefore, the comparison returns `null` and sets the default as `null`. Then further installation scripts would stop because of the `Cannot find PHP [null] in the list` error.

  Valet will now convert the drive letter to a lowercase via the native `lcfirst` PHP function in both `addDefaultPhp` function of `Configuration` class and in the `php:add` command. So now the default PHP should never be `null`.

- Fixed https://github.com/yCodeTech/valet-windows/issues/3 where upon a fresh installation of Valet with no config.json, it would try to read the config even if it's not yet created and spits out an "Failed to open stream: No such file" error. Fixed by adding a check to see if the file exists in the `read` function of the `Configuration` class (fix PR by @hemant-kr-meena in https://github.com/yCodeTech/valet-windows/pull/4).

## For previous versions prior to this repository, please see [cretueusebiu/valet-windows](https://github.com/cretueusebiu/valet-windows), of which this is an indirect fork of.
