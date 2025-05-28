# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/yCodeTech/valet-windows/tree/master)

## [3.2.0](https://github.com/yCodeTech/valet-windows/tree/v3.2.0) - 2025-05-14

This is a large [Release PR](https://github.com/yCodeTech/valet-windows/pull/18) that adds new `php:proxy` and `share-tool` commands, lots of refactoring for improved maintainability, dynamically downloading the latest versions of the required executables from GitHub, and brings a lot of the code inline with macOS Valet. For the full changelog please view the PR commits.

### Removed

-   Removed the deprecated (since v3.1.0) confirmation question about uninstalling the outdated cretueusebiu package in the `install` command. **Doesn't affect valet functionality.**

-   Remove references to the defunct `xip.io`, inline with macOS Valet.

-   Removed unused code:

    -   `Filesystem::symlink` method in favour of `Filesystem::symlinkAsUser` as the new `Filesystem::symlink` method.

    -   `SUDO_USER` from the `user` helper function since Windows doesn't set `SUDO_USER` environment variable at all, so this is redundant code.

    -   `array_is_list` helper function, which was a polyfill for the PHP 8.1 function and was introduced in commit [a7312ef](https://github.com/yCodeTech/valet-windows/commit/a7312ef). But has been unused since commit [e30fd4f](https://github.com/yCodeTech/valet-windows/commit/e30fd4f) (where the usage of `array_is_list` in `addTableSeparator` function was removed). So we can safely remove it.

    -   `str_ends_with` and `str_starts_with` helper functions as these will be handled by the Symfony dependency.

-   Removed all bin files for `gsudo`, `ansicon`, `WinSW`, and `nginx`, in favour of downloading the latest versions from GitHub via the API.

### Added

-   Added new `php:proxy` command with the alias of `php` to proxy PHP commands through to a site's PHP executable. This is inline with macOS Valet.

    -   The command is defined in `valet.php`, while the logic resides in the `valet` script in the project root.

    -   Added new `PhpCgi::getPhpPath` method to get the PHP executable path by specifying a version.

    -   Changed `PhpCgi::findPhpVersion` method to return the executable path if the new `getExecPath` param is `true` otherwise it will default to returning the PHP version. Also changed it's error return value to `false` for easier checking.

    -   Changed `Site::whichPhp` method to extract the PHP version from the ansi coloured output, and the raw version string to a new element in the return array to avoid weird errors when using it in the new `php:proxy` command.

    -   Changed the info output in `php:which` command to use the new raw phpVersion string, and added a separate info output to get and display the php executable path using the new `PhpCgi::getPhpPath` method.

-   Added new `share-tool` command to get or set the name of the currently selected share tool. This is inline with macOS Valet. Only supported option at the moment is `ngrok`, which is the default tool in the config.

-   Added new `Share` and `ShareTools\ShareTool` classes to setup the supported share tools and communicate with the share tool classes.

    -   `Share` class defines methods to set and get the share tools (eg. `ngrok`), and setup the share tool class instance to be able to chain their methods.

        -   Added methods:

            -   `shareTool` is the main method to use which returns the share tool's class instance to be able to access the methods in a chainable way.

            -   `createShareToolInstance` to create the share tool child class instance.

            -   `getShareToolInstance` to get the share tool child class instance.

            -   `getShareTools` to get all the supported share tools as a string.

            -   `isToolValid` to check if the specified tool is valid.

            -   `getCurrentShareTool` to get the current share tool from the config.

    -   `ShareTool` (abstract) class with the namespace `Valet\ShareTools` is the base class that all other share tool classes will extend and implement required methods, it also holds some shared methods.

        -   Added methods:

            -   `start` (abstract) to start sharing. A share tool class is required to implement the method since each tool could have different steps.

            -   `run` (abstract) to proxy CLI commands through to the tool's executable and run them. A share tool class is required to implement the method.

            -   `getConfig` to get the config path. A share tool class is required to implement the method.

            -   `currentTunnelUrl` to get the current tunnel URL from the API. The method was moved from `Ngrok::currentTunnelUrl` into this new shared method.

            -   `findHttpTunnelUrl` to find the HTTP tunnel URL from the list of tunnels. The method was moved from `Ngrok::findHttpTunnelUrl` into this new shared method.

    -   Moved `Ngrok` class into the new `ShareTools` namespace, and changed it to extend the new `ShareTool` class.

    -   Renamed `Ngrok::getNgrokConfig` method to the new `Ngrok::getConfig` and changed all references.

    -   Changed the `Ngrok` class in `fetch-share-url` command for the new `Share` class.

    -   Refactored `ShareTool::currentTunnelUrl` to loop through all tunnel endpoints, which is inline with the macOS Valet.

    -   Changed `findHttpTunnelUrl` method back to using the object operator, that was changed in commit [2ea79e6](https://github.com/yCodeTech/valet-windows/commit/2ea79e6). Because we are no longer changing the object to an array, so we need to change it. This is more inline with macOS Valet code.

    -   Changed `set-ngrok-token`, `share` and `fetch-share-url` commands to ensure that they don't run if a share tool isn't set. Also `set-ngrok-token` shouldn't be able to run when `ngrok` is not the current share tool. This is useful for when there are more share tools to use.

    -   Changed `share` command to use the new `Share` class and using the `shareTool` method to get the current share tool's class instance and access the tool's `start` method directly via method chaining.

-   Add `symfony/polyfill-php80` dependency to polyfill various PHP 8.0 functions for backwards compatibility, including `str_contains`, `str_starts_with`, and `str_ends_with`. https://github.com/symfony/polyfill/blob/1.x/src/Php80/README.md

-   Added new `Upgrader` class to define methods that will run every time valet is ran.

    -   Removed the `Configuration::prune` and `Site::pruneLinks` method calls from `valet.php` in favour of the new methods in this class.

    -   Added methods:

        -   `onEveryRun` to call all the other `Upgrader` methods so they can run on every valet run.

        -   `prunePathsFromConfig` to prune all non-existent paths from the configuration (uses `Configuration::prune`).

        -   `pruneSymbolicLinks` to prune all symbolic links that no longer point to a valid site (uses `Site::pruneLinks`).

        -   `upgradeSymbolicLinks` to upgrade and convert all Windows junction links to real symbolic links. This is a one-time upgrade that will be run when Valet is first installed.

        -   `lintNginxConfigs` to lint the Nginx configuration files. This is just a wrapper around the `Nginx::lint` method.

        -   `upgradeNginxSiteConfigs` to upgrade Nginx site configurations if they contain deprecation warnings for `http2` param and `http2_push_preload` directive.

    -   Added a call to `onEveryRun` method in `valet.php`.

-   Added new `Filesystem` methods:

    -   `isFile`, which is a wrapper around the PHP `is_file` function to check if a path is a normal file.

    -   `convertJunctionsToSymlinks` to remove the Windows junction links, and re-link the sites as real symlinks.

    -   `getJunctionLinks` to get all the site links that are junctions.

    -   `move` method to rename a filepath which moves it to a new location, eg. `my/path/to/file.txt` --> `new/path/to/file.txt`. The filepath can be a file or directory. If it's a directory, the contents will be moved at the same time. If the destination directory or any subdirectories does not exist, it will be created.
        This uses the PHP `rename` function, and is the [PHP docs](https://www.php.net/manual/en/function.copy.php) recommended way for moving files:

        > "If you wish to move a file, use the rename() function."

        (Originally, PHP's `copy` function was used in this method, but it doesn't create directories if they don't exist, causing errors.)

    -   `unzip` method to unzip a zip file into the specified location.

    -   `listTopLevelZipDirs` to list top-level directories in a zip file.

    -   `getStub` method to get the specified stub file. This is inline with macOS Valet. This gets the contents of a file from the internal `stubs` directory, but if the user has a custom stub file in a `stubs` directory in the home path (`~/.config/valet/stubs`), then we use the custom stub instead.

        -   Changed all references of getting a file from the `stubs` directory to use the new `getStub` method, which makes the code slightly DRYer as it's not constantly repeating the `stub` path.

-   Added new `GithubPackage` abstract class in the new `Packages` directory with the namespace `Valet\Packages`. This is the base class that all other package classes will extend.

    -   Added methods:

        -   `install` (abstract) to allow a package class to define the `install` method since each package could have different install steps.

        -   `isInstalled` to check if the package is installed (ie. the package executable exists).

        -   `download` to download the package files from GitHub API via Guzzle.

        -   `packagePath` to get the path to the package directory.

        -   `packageExe` to get the path to the package executable.

        -   `cleanUpPackageDirectory` to clean up the downloaded package's directory, removing all unnecessary directories and files.

        -   `getUnnecessaryDirs` to get the unnecessary directories to remove in the package directory.

        -   `removeZip` to remove the zip file after extracting its contents.

        -   `moveFiles` to move files from the extracted directory into the main package directory.

        -   `getVersionedFilename` to get the versioned filename from the asset name, replacing the `VERSION` placeholder with the version number obtained via regex. This is so we can get the nginx zip file which is named with it's version.

    -   Added `packageName` property, so that all the package classes can define it with the package's name as value. Allowing the use of the property within the `GithubPackage` class to dynamically refer to the package that the current child class is referring to. This makes methods like `isInstalled`, `packagePath`, and `packageExe` easier to read and use, without the need for passing an arg for the package name, it just uses the dynamic property instead.

-   Added new `Gsudo`, `WinSW`, and `Nginx` classes with the namespace `Valet\Packages` that extend the `GithubPackage` class. These classes will download the latest versions from the GitHub API.

    -   `Gsudo` class:

        -   Added methods:

            -   `install` to download and install the latest version of Gsudo from GitHub releases.

            -   `configureGsudo` to configure Gsudo's settings.

            -   `runAsSystem` to run Gsudo as the Local System account.

            -   `runAsTrustedInstaller` to run Gsudo as the Trusted Installer account. Required for `Filesystem::symlink` method.

        -   Added a call to the `install` method in Valet's commands `install` and `sudo` to install the Gsudo package from GitHub if it's not already installed.

    -   `WinSW` class:

        -   Added methods:

            -   `install` to download and install the latest version of WinSW from GitHub releases.

            -   `changeReadme` to change the downloaded `readme.md` to:

                -   Add the release version to the top of the file so we can detect what version it is by reading it.

                -   Replace relative links with absolute links to the source code and docs on GitHub. (For dev usage.)

        -   Added a call to the `install` method in the `WinSWFactory::__construct` method to download and install the WinSW package from GitHub if it's not already installed.

    -   `Nginx` class:

        -   Added methods:

            -   `install` to download and install the latest version of Nginx from GitHub releases.

            -   `moveNginxFiles` to move the required Nginx files into the package directory. This is needed as it loops through and the zip directories, and finds the correct name of the directory needed to move.

        -   Added a call to the `install` method in the `Nginx::install` method. So that everytime Nginx is installed, it also calls the `Packages\Nginx` to download and install the Nginx package from GitHub if it's not already installed.

-   Added `getTarExecutable` helper function to return the path to `tar.exe` which resides in `C:\Windows\System32` directory.

    This is needed because just using `tar` as a command might not get the correct `tar` syntax if bash is installed. Bash also installs it's own global version of the `tar` command with a very different set of options. So outright specifying the Windows `tar` executable path prevents any clashes. Used in `Filesystem::unzip` method.

-   Added new `str_contains_any` helper function, to check if the string includes any of the strings in an array. There is no PHP native function equivalent to this, so it just loops through the array of needles and uses the PHP native `str_contains` function under the hood to check a singular string within a string. This is used in the `Upgrader::upgradeNginxSiteConfigs` method.

-   Added new `ValetException` class which extends the PHP native `Exception`, and holds various methods to help construct the `Exception` error messages.

    -   Added methods:

        -   `getError` to construct and get the error message. The code was moved from the `error` helper function into this new public method, with a few refactorings. This will be the method that `error` helper function will use to obtain the error message.

        -   `getErrorTypeName` to get the error type name from the error code. The method was moved from the `getErrorTypeName` helper function into this new private method.

        -   `constructTrace` to construct a better-formatted error trace. The code was moved from the `error` helper function into its this new private method, with a few refactorings.

        -   `githubApiRateLimitExceededError` to handle the GitHub API "rate limit exceeded" error from the `GithubPackage::download` method. This gets the rate limit and limit reset time from the API response headers, and extracts the IP address from the original response error via regex. It then displays the error in the terminal with more information than the original error would have displayed.

        -   `calculateTimeToGithubApiLimitReset` to calculate the time left for GitHub API rate limit to reset when the API sends the "rate limit exceeded" error from the `GithubPackage::download` method. It uses the reset time from the API headers, and gets the time difference from the current time. We then get the difference in minutes and seconds, and construct a human-readable string for the error output.

    -   Changed `error` helper function to get the constructed `ValetException` error message via `ValetException::getError` method and output it to the console.

-   Added new `Diagnose` commands to output the version number of `gsudo`, `ansicon`, and `acrylic`.

    Gsudo and Ansicon use the `packageExe` method of their respective package class and use their CLI commands to get the versions. But Acrylic doesn't have a CLI command, so we have to get the contents of it's Readme.txt, and find the version later on.

    -   Added a conditional to the `editOutput` method to edit the output of Acrylic's Readme.txt content. We use regex to find the version number within the file contents, and only output the version number.

### Changed

-   Version bumped the macOS valet URL in the `parity` command to make sure the command parity is up to date with the latest MINOR version at the time of release.

-   Update `ngrok` executable to v3.22.0

-   Bumped versions of composer dependencies: `composer/ca-bundle` and `guzzlehttp/guzzle`

-   Refactored the `user` helper function to have the `USERNAME` variable return by default, and only return the `USER` if it's set.

-   Extracted much of `server.php` into a new `Server` class, inline with the macOS Valet; simplifying a lot of the code and renaming some methods.

-   Refactored the requiring of valet driver files for simplicity by looping through the directory, instead of listing all the files to include.

-   Refactored `Filesystem::unlink` for simplicity.

    -   Removed the `is_dir` check from the symlink part of the code since the `unlink` and `rmdir` does both symlinked files and folders.

    -   Remove `file_exists` check since it was only checking for files and the code is the same as the symlink. Combine it into the `isLink` check to also check for files with the new `isFile` method instead of `file_exists`.

    -   Refactored `isLink` method since we're dealing with real symlinks now instead of junctions, so the PHP `is_link` function works as intended now.

-   Changed `Ansicon` class:

    -   Moved the class into the `Packages` directory under the new namespace `Valet\Packages`, and changed it to extend the new `GithubPackage` class.

    -   Changed the `install` method to download the latest version from the Github API using Guzzle and unzip the zip file before running the `runOrExit` installation code.

        The method also creates a `readme.md` file with the contents of the `readme.txt`, just to make it easier to read for dev purposes.

    -   Changed the `runOrExit` code in both `install` and `uninstall` methods to use the parent class's new `packageExe` method, which gets the executable path of the package.

    -   Removed the `__construct` methods from this new `Ansicon` class, since it will now be using the parent's `__construct` method instead.

-   Changed the name of the WinSW executable from `WinSW.NET4.exe` to just `winsw.exe` in the `copy` call of the `WinSW::createConfiguration` method. This is because the executable needs to be the same name as the directory, otherwise the new `GithubPackage::packagePath` method wouldn't work.

-   Changed the `CommandLine::sudo` method to use the new `Gsudo::runAsTrustedInstaller`, and `Gsudo::runAsSystem` methods, instead of explicitly hardcoding the executable path and command flags in this `CommandLine` method. This uses the `resolve` helper function to setup a new `Gsudo` class instance and access the methods in the normal object way.

-   Changed the `Nginx::lint` method to:

    -   Allow it to return the output to the calling function instead of the terminal if the `returnOutput` variable is `true`.

    -   Redirect the stderr of nginx's configuration test command to stdout so we can catch the errors in the output if returning. Ie. added `2>&1` to the nginx command.

    -   Use the `error` helper function to throw an `Exception` and colour code the output instead of throwing a `DomainException` by itself which has no colour output.

    -   Colour the output as a `warning` if the `outputContent` contains the word `warn`. Otherwise, a standard `output` will be used.

-   Combined redundant `COMPOSER_GLOBAL_PATH` constant into the `Diagnose` command.

    -   Removed the redundant `COMPOSER_GLOBAL_PATH` constant because it's only used by one thing. So it doesn't need to exist.

    -   Changed the `Diagnose` command to get the contents of the global `composer.json` to use the code that was originally used to set the `COMPOSER_GLOBAL_PATH` constant. Ie. The command now directly uses the `Valet::getComposerGlobalPath` method to get the global composer path.

-   Refactored `Diagnose` commands, making them DRYer:

    -   For nginx to use the new `packagePath` and `packageExe` method of the `Packages\Nginx` class.

    -   That gets the contents of valet's `config.json` to use the `Configuration::path` method instead of repeating it's path (`~/.config/valet/config.json`).

### Fixed

-   Fixed `Filesystem::symlinkAsUser` method to:

    -   Create a real symbolic link instead of a Windows directory junction link.

        As per the [docs](https://learn.microsoft.com/en-us/windows-server/administration/windows-commands/mklink), `mklink` params are `/D` for symbolic link, `/H` for hard link, and `/J` for directory junction.

        Previously, site links are created as a directory junction. This is not a real symbolic link, and PHP's `is_link` function always fails. Fixed by using the proper param `/D` to create a real symlink.

    -   Renamed the method to `symlink` since `AsUser` doesn't make sense in this context.

    -   Changed how valet runs the `mklink` command. It now uses `CommandLine::sudo` to run with trusted installer privileges since the `/D` param requires trusted installer privileges not just elevated privileges.

    -   Fixed unwanted output from `mklink` Windows command by adding a quiet mode to `sudo`.

-   Fixed the check in `Filesystem::isBrokenLink` method to determine if the resolved symlinked path doesn't exist by using `file_exists` PHP function. This is because `readlink` will always resolve it's target path and will never return false, so it doesn't really check if it exists or not.

-   Fixed `certutil` requiring elevated privileges for SSL certificates.

    When securing/unsecuring a site, valet uses `certutil` Windows command to add/delete SSL certificates from the machine store, which requires elevated privileges. But `certutil` has a `-user` option to store the certificates in the user store instead of machine store, which doesn't require privileges. So added the option to all `certutil` usages.

-   Fixed `Site::isSecured` method that was failing due to double TLDs (`.tld.tld`, eg. `.test.test`). This is easily fixed by removing the TLD if it's provided before the method continues which re-adds the TLD.

-   Fixed `Site::isolate` and `Site::unisolate` methods from spamming the `Nginx::stop` and `Nginx::restart` methods.

    The `Nginx::stop` and `Nginx::restart` methods are spammed if the `Site::isolate` or `Site::unisolate` methods are used in a loop, causing a plethora of Windows UAC popups (if `sudo` wasn't used).

    Fixed by removing the `Nginx` calls within the `Site` methods and added the `Nginx::restart` method call to the relevant command definitions in `valet.php` after where `Site::isolate` or `Site::unisolate` are called.

-   Fixed nginx's deprecated `http2` param and `http2_push_preload` directive.

    As of nginx 1.25.1 (we're using the latest 1.28.0), the `http2_push_preload` directive and the `http2` param of the `listen` directive are deprecated. To fix we need to remove them.

    -   Removed the `http2` param of the `listen` directive in all relevant `*.valet.conf` stub files.

    -   Removed the `http2_push_preload` directive in all relevant `*.valet.conf` stub files.

    -   Added the recommended `http2` directive instead of the param on `listen`, in all relevant `*.valet.conf` stub files.

-   Fixed the `PHP Deprecated: Creation of dynamic property Valet\Diagnose::$progressBar` error which is caused by the `progressBar` property being dynamically created in `Diagnose::beforeRun` method.

    The creation of a dynamic property without being defined first is deprecated in PHP 8.2. See https://php.watch/versions/8.2/dynamic-properties-deprecated for more info.

    To fix the deprecation error, we just need to declare the property in the class first.

## [3.1.7](https://github.com/yCodeTech/valet-windows/tree/v3.7) - 2025-03-22

### Fixed

-   Fixed `Ngrok::hasAuthToken` method to explicitly check for an `authtoken` property within the ngrok config file, when the config file already exists.

    Previously, the method only checks if the config file exists, if it doesn't then the authtoken isn't set. But what if the user added the config manually or edited the file? The method would then always `return true` even though the token might not exist.

    Fixed by adding an explicit check of the contents if the file does exist. The contents of the yml config file is read and converted to an associative array.

    From this we can then check if the config version is 2 or 3 (v3 has a new config format), and then we can check if the `authtoken` key exists. In v3 the `authtoken` is defined in a separate `agent` key, so the checks need to be adjusted.

-   Fixed `Ngrok::run` method to check if the command proxying to ngrok executable is the `update` or `config upgrade` command, if it is then append the ngrok config flag to it. This is just so ngrok doesn't complain it can't find the config, and so we can upgrade the config to v3 without the user specifying the config location.

### Added

-   Added `symfony/yaml` dependency to convert contents of a `.yml` file to an associative array. (Required for the fixed `Ngrok::hasAuthToken` method.)

-   Added various output messages to the terminal for `Ngrok::start`, so it's more descriptive as to what's happening.

-   Added new `CommandLine::shellExec` method to execute the command in the terminal, and also be able to return the output as a string to the calling method. This is just a wrapper around the native PHP `shell_exec` function.

### Changed

-   Dependency version bump for `mnapoli/silly` to 1.9

-   Refactored `Ngrok::getNgrokConfig` method to optionally return only the path of the config file (without the leading `--config` cli flag). (Required for the fixed `Ngrok::hasAuthToken` method.)

-   Changed `Ngrok::start` method to output a message if ngrok errors that the executable is "too old" for the account:

    > ERROR: authentication failed: Your ngrok-agent version "3.3.1" is too old. The minimum supported agent version for your account is "3.6.0". Please update to a newer version with ngrok update...

    Because we can't test ngrok commands for errors or errors in the executable (which was tested in commit [f8be62b](https://github.com/yCodeTech/valet-windows/commit/f8be62b), but reverted in [a449582](https://github.com/yCodeTech/valet-windows/commit/a449582)), we need to return the output of any errors and check the output for a specific error code relating to the "too old" error (`ERR_NGROK_121`). Then output a message (along with the original error output) to the user to inform them that they can update ngrok executable and also upgrade the config file themselves by performing the valet commands: `valet ngrok update` and `valet ngrok config upgrade` respectively.

    All errors other errors will still output to the terminal.

### Removed

-   Removed the functionality of starting a new CMD window in `Ngrok::start` of the `share` command, since a new window will surpress any errors. We need to be able to output the errors as and when they happen.

-   Removed the `--debug` flag from the `share` command since this is now defunct with the removal of the new CMD window. (Debug only prevented the CMD window opening, forcing errors to be logged directly to the terminal.) (Not a breaking change.)

## [3.1.6.1](https://github.com/yCodeTech/valet-windows/tree/v3.1.6.1) - 2025-03-11

### Fixed

-   Fixed version number because in v3.1.6 release, Valet didn't have it's version bumped, so it was displaying as v3.1.5. Fixed by bumping version to v3.1.6.1

## [3.1.6](https://github.com/yCodeTech/valet-windows/tree/v3.1.6) - 2025-03-06

### Added

-   Added emergency batch script to stop and uninstall services if errors occur when running `composer global update` without uninstalling valet first. This happens because the services are still installed and therefore the files cannot be removed and updated since they're in-use. The batch script is to emergency stop and uninstall the services, so a subsequent composer update is able to work.

### Fixed

-   Fixed `php:which` command to obtain the linked sites and merge with the parked sites array. Previously, it was only getting the parked sites, so not all sites were obtained and queried.

## [3.1.5](https://github.com/yCodeTech/valet-windows/tree/v3.1.4) - 2025-03-03

### Fixed

-   Fixed a deprecation notice in PHP 8.4:

    > Valet\CommandLine::run(): Implicitly marking parameter $onError as nullable is deprecated, the explicit nullable type must be used instead.

    When an optional function argument is typed eg. `callable $onError = null`, it is implicitly casted to `null` via it's default value, this is now deprecated in 8.4. Usually we can just use a union type `callable|null`, this is explicitly tells PHP it is either `callable` **or** `null`. However, union types were only introduced in 8.0.
    To keep backward compatibility for PHP 7, we can use the nullable type operator (`?`) instead.

    Using the nullable type operator fixes the deprecation notice in 8.4 and is backwards compatible for PHP 7 and 8.

## [3.1.4](https://github.com/yCodeTech/valet-windows/tree/v3.1.4) - 2024-08/06

### Fixed

-   Fixed a bug with AcrylicDNS when Valet is installed on Linux on Windows via WSL2, WSL2 wouldn't launch on system reboot (Fix PR by @jerrens in https://github.com/yCodeTech/valet-windows/pull/15).
    -   Changed the binding address to `127.0.0.1` instead of `0.0.0.0`. Fixes https://github.com/yCodeTech/valet-windows/issues/14

## [3.1.3](https://github.com/yCodeTech/valet-windows/tree/v3.1.3) - 2024-05-23

### Fixed

-   Fixed Valet install failure, particularly with Ansicon due to spaces in the user directory name (Fix PR by @shahriarrahat in https://github.com/yCodeTech/valet-windows/pull/12).

    -   Adds a `pathFilter` function to replace the directory name with it's Windows shortname equivalent. e.g. from `John Doe` to `JOHNDO~1`. For use with the `valetBinPath` function that is used in the Ansicon installation.

-   Fixed multiple cmd or powershell commands for spaces by wrapping them in double quotes.

-   Fixed errors where composer diagnostics and the valet diagnostics output file couldn't be written because it was trying to write to the terminal's current working directory, which could be a protected directory like `Program Files`. Added `VALET_HOME_PATH` to the commands, so that they get saved to `~/.config/valet`.

-   Fixed the copying of the diagnostics output to clipboard that just stopped working for unknown reasons. Fixed by changing `cli->run` to `cli->powershell` to ensure that the copy command `clip` is available.

-   Fixed the nginx config check command in `diagnose`, where it errors out because it couldn't find the file due to:

    -   the path after the `-c` option couldn't be the shortened username via the `valetBinPath()`. Changed it back to use `__DIR__` . And added an escaped double quotes (`\"`) around it.

    -   the replacing of all backslashes in the command. This meant that with the new escaped double quotes it changed the backslash to forward slash. So instead of `-c \"C:/Users/...` it was changed to `-c /"C:/Users/...` and the system interpreted it as `"C:/C:/Users/...`

        Fixed by changing the replace function to use a regex that only replaces single backslashes and disregards the escaped quotes.

### Changed

-   Changed `pathFilter` function from https://github.com/yCodeTech/valet-windows/pull/12 to replace forward slashes with backslashes to prevent errors within the function where paths aren't exploded because the `/`s. But replaced the backslashes back to forwardslashes once the function was complete and fix further errors that occurred.

-   Moved the `diagnose` commands array into the `__construct` function so that we can use the global `valetBinPath` function and allow the paths to also be changed to the short username if needed.

-   Changed various `diagnose` array commands to use the `valetBinPath` function and the command that has the `COMPOSER_GLOBAL_PATH` to use the `filterPath` function.

## [3.1.2](https://github.com/yCodeTech/valet-windows/tree/v3.1.2) - 2024-05-17

### Fixed

-   Fixed https://github.com/yCodeTech/valet-windows/issues/11 bug that couldn't find the PHP executable when trying to add the default when installing valet. It would fail to get the PHP when there is a space in the path like `c:/Program Files/php/`. Wrapping the path in quotes in the underlying cmd command fixes this.

-   Fixed an issue with the `diagnose` command which was brought up in https://github.com/yCodeTech/valet-windows/issues/11. The command wasn't available if valet wasn't installed, and could never be installed because of the PHP bug above. Fixed this by always having `diagnose` command available whether valet is installed or not.

## [3.1.1](https://github.com/yCodeTech/valet-windows/tree/v3.1.1) - 2024-03-25

### Added

-   **Aesthetic only** - Added the logo as an ASCII art to the namespace command `valet`; and the `valet list` command, via extending Silly's Application class.

### Changed

-   Updated dependencies for Laravel installer to add support for Laravel 11, (PR by @
    onecentlin in https://github.com/yCodeTech/valet-windows/pull/10).

## [3.1.0](https://github.com/yCodeTech/valet-windows/tree/v3.1.0) - 2024-02-28

### Added

-   Added new `parity` command to get a calculation of the percentage of parity completion against Laravel Valet for macOS.

### Changed

-   Updated dependencies for Laravel installer to add support for Laravel 10, (PR by @hemant-kr-meena in https://github.com/yCodeTech/valet-windows/pull/8).

### Fixed

-   Fixed https://github.com/yCodeTech/valet-windows/issues/6 PHP 8+ deprecation notice for required parameters declared after optional parameters.

    The notice would only show when the PHP ini `error_reporting` setting wasn't ignoring deprecation notices, and all reports are enabled, ie. `E_ALL`. This notice would then be displayed in the terminal on every `valet` command. Though it doesn't impact valet's functionality, it could potentially be broken in future PHP versions.

    Fixed by:

    -   Adding a default `null` value to the `$debug` parameter for the optional `--debug` option of the `share` command.

    -   Removing the default `null` value from the `$key` parameter of the optional `key` argument of the `log` command. Because the key is used to specify the name of a specific log and omitting the argument will make valet list all the log file names, it doesn't need to have a default value.

### Deprecated

-   The `install` confirmation question of outdated cretueusebiu package. Doesn't affect valet functionality.

## [3.0.0](https://github.com/yCodeTech/valet-windows/tree/v3.0.0) - 2023-09-21

### Added

-   Added support for multiple PHP services, (Feature PR by @iamroi in https://github.com/cretueusebiu/valet-windows/pull/195).

    -   Enables the use of the previously disabled `valet use` command, to switch the default PHP version used by Valet.
    -   Adds new commands `php:add`, `php:remove`, `php:install`, `php:uninstall`, `php:list`, `xdebug:install`, `xdebug:uninstall`.

-   Added PHP version to the `links` command output (Patch 1 PR by @damsfx in https://github.com/iamroi/valet-windows/pull/1).

-   Added PHP version to the `parked` command output, improvements to the Patch 1 above and a new command `php:which` (Patch 2 by @yCodeTech in https://github.com/yCodeTech/valet-windows/pull/1)

    -   Adds `default` or `isolated` to the PHP version output, with the latter being coloured green for emphasis in both `parked` and `links` commands. This acts as a 2 in 1, showing the PHP version, and determines whether the site is isolated.

    -   Adds `Alias` and `Alias URL` to the `parked` command output. If the parked site also has a symbolic link, it's linked name (aka alias) and alias URL will be outputted in the table.

    -   Adds new `php:which` command to determine which PHP version the current working directory or a specified site is using.

    -   Changes the output table to vertical for easier reading, using Symfony's `setVertical` method (only works when Valet is installed on PHP 8.1).

-   Added new `isolate` and `unisolate` commands. Isolates the current working directory or a specified site (also specify multiple sites) to a specific PHP version, and removes an isolated site (also unisolate all sites), respectively.

-   Added `version_alias` to the `addPhp` function in the `Configuration.php` file, creating an alias name for the full PHP version, which is then written to the user's Valet `config.json`. A full PHP version of 8.1.8 will have the alias of 8.1, the alias can then be used in commands.

-   Added PHP's `krsort` function to the `addPhp` function in the `Configuration.php` file, so that the PHP array in the user's Valet `config.json` is written in a natural decending order that adheres to decimals.

    Natural decending order example:

    ```
    8.1.18

    8.1.8

    7.4.33
    ```

    This means that when two different patch versions of the same SemVer MAJOR.MINOR version of PHP is added like 8.1.8 and 8.1.18; and then the `use` command is ran with the alias version like 8.1, then the default will be set to the most recent version of that alias. In this example, it would be 8.1.18.

-   Added `isolated` command to list all the isolated sites.
-   Added `Version Alias` to the table output of `php:list`.
-   Added `--isolate` option to the `link` command to optionally isolate the site whilst making it a symbolic link.
-   Added the ability to unsecure a site when a secured linked site is unlinked before removing the site's conf to ensure it's removed from Windows internal certificate store.
-   Added the ability to unisolate a site when an isolated linked site is unlinked, to ensure it removes it properly.
-   Added `secured` command to list all the secured sites.
-   Added Valet Root CA generation and sign TLS certificates with the CA (PR by @shawkuro in https://github.com/cretueusebiu/valet-windows/pull/179).
-   Added row separators for horizontal tables.
-   Added `sites` command to list all sites in parked, links and proxies.
-   Added `set-ngrok-token` to set ngrok authtoken and added a command alias for it: `auth`.
-   Added `--debug` option to the `share` command to prevent the opening of a new CMD window, and allow error messages to be displayed from ngrok for easier debugging. This is needed because ngrok may fail silently by opening a new CMD window and quickly closes it if it encounters an error, so no errors are outputted.
-   Added an `--options` option to the `share` command to pass any ngrok options/flags for the ngrok `http` command, which Valet will pass through to ngrok. Also added a shortcut `-o`. See the [docs](https://github.com/yCodeTech/valet-windows/blob/master/README.md#notes-for-all---options) for information on how this works.
-   Added `sudo` command and [gsudo](https://github.com/gerardog/gsudo) files. The new command is to `passthru` Valet commands to the commandline that need elevated privileges by using gsudo. gsudo is a `sudo` equivalent for Windows, it requires only 1 UAC popup to enable the elevation and then all commands will be executed as the system instead of having multiple UACs opening.

    Also added an error message for if `valet sudo sudo` is ran, because you can't sudo the sudo command.

-   Added `valetBinPath` helper function to find the Valet bin path, and updated all the code to use it.
-   Added a check to see if a site is isolated before unisolating it.
-   Added command example usages to display in the console when using `--help`.
-   Added a progressbar UI to `services` function, and `install`, `uninstall`, `restart`, `stop` commands to improve the UX.
-   Added `error` output to the `getPhpByVersion` function to cut down on duplicate `error` code that relates to the function.
-   Added a sleep for 0.3s (300000 microseconds) in between the `uninstall` warning and the question to allow the warning be output before the question is outputted. And simplified the if statements.
-   Added a command alias of `unpark` to the `forget` command.
-   Added a composer conflict for the old unmaintained cretueusebiu/valet-windows version, just so composer can't install this 3.0 version alongside it.
-   Added parity related additions for proxying.
    -   Added `--secure` option to `proxy` command.
    -   Updated the proxy stub to be the unsecure proxy stub as default.
    -   Changed the `proxyCreate` in `Site` class to accommodate for the new `--secure` option.
    -   Added new `secure.proxy.valet.conf` stub for the secure proxy.
    -   Changed `resecureForNewTld` to check for the new `secure.proxy` stub to ensure it keeps it secured when reinstalling Valet.
    -   Added support for proxying multiple sites at once by separating them with commas, in both `proxy` and `unproxy` commands.

### Changed

-   Changed package namespace to `yCodeTech`.
-   Changed capitalisation from `valet` to `Valet` in various outputs and code comments where the don't refer to the commands.
-   Changed the output table to vertical for easier reading on those longer columns, with an optional argument to draw the table horizonally.
-   Renamed the `usePhp` function to `isolate` in `Site.php` file to reflect it's command name.
-   Updated ngrok to the latest version of 3.3.1
-   Moved Valet's version variable out and into it's own separate file for ease.
-   Changed various function return types.
-   Changed output tables `SSL` columns to `Secure` for easier understanding.
-   Changed `error` helper function to throw an exception when specified to do so, and add more meaning to the error output by constructing the error message from the `Exception` class. This is because sometimes the exception doesn't output the exact error or file names needed in order to debug. So reconstructing the error from the class methods should fix it.

-   Changed the table style to `box` which outputs solid borders instead of using dashes.
-   Changed the name of the `starts_with` and `ends_with` helper functions to `str_starts_with` and `str_ends_with` respectively to reflect the PHP 8+ functions.
-   Updated various output texts.
-   Changed the way the `secure` command was getting the current working directory to use the `getSiteURL` function instead.
-   Changed various `warning`s to `error`s.
-   Changed `domain` text and variables to `site` to properly reference the `site`.
-   Changed text to use the proper capitalisation of `Xdebug`.
-   Changed the 404 template to be more visually appealing by adding the Valet 3 logo - the logo also acts as a clarification that if the 404 happens we know it's something to do with Valet and nothing else.
-   Changed Xdebug's installation behaviour to no longer install automatically, without specific flag being present. This is because Xdebug is only a PHP debugging service, so if it's not used, then it's wasting a bit of resources.

    -   Added an `--xdebug` option to the commands `php:add` and `install` to optionally install Xdebug while installing the PHP or installing Valet respectively.
    -   Added an optional `phpVersion` argument to the commands `xdebug:install` and `xdebug:uninstall` to install or uninstall Xdebug for a specfic PHP version. If installing and the version is already installed, ask the user if they want to reinstall it.
    -   Added function to check if a supplied PHP version is the alias or not, and a function to get the full PHP version by the alias. Used in `PhpCgiXdebug` and `PhpCgi` files.
    -   Added a function to check if Xdebug of a specfic PHP version is installed, or if a version isn't supplied then check if any version is installed for the PHP installed in Valet. Used for many of the commands to uninstall if it is installed.
    -   Added the service ID to `WinSwFactory` to allow `WinSW` functions get the and use the full ID in order to fully check if it's installed. Used in `PhpCgiXdebug`, `PhpCgi`, and `Nginx` files.
    -   Changed Xdebug service name.
    -   Changed the powershell cli command of the `installed` function of `WinSW` file to use the newly added service ID instead of the name. And removed the now unnecessary extra code.
    -   Changed various warning outputs to errors.
    -   Removed the `getPhpCgiName` function from `PhpCgiXdebug` class because the function exists in the parent class and should be used instead, thus removing duplicate code.
    -   Fixed `xdebug:install`, previously, when no PHP version is passed, the command will reinstall Xdebug even if it's already installed without asking the user. Fixed so that it asks just as it does when a PHP version was passed. Changed the output text accordingly.
    -   Removed the redundant `isInstalledService` function in favour of using the `installed` function of `WinSW`, as it does exactly the same, thus removing duplicate code.

-   Changed the path argument of `php:add` to required rather than optional (removed the square brackets).
-   Changed the `services` function in the `Valet.php` file to output `not installed` instead of `missing` for the Xdebug services, because it's not essential for Valet to run, so it shouldn't be labelled as missing.
-   Replaced the `DIRECTORY_SEPARATORs` and `\\` for `/` in all paths and using `str_replace` to replace `\\` into `/`, so there isn't any weird paths like `C:\\sites/mysite`.
-   Overhauled the `diagnose` command.

    -   Changed `Diagnose` class to use the `progressbar` helper function instead of initiating Symfony's `ProgressBar` class separately.

    -   Removed commands not applicable for Windows.

    -   Added various other commands that will be necessary to debug.

    -   Added a `COMPOSER_GLOBAL_PATH` constant to the helpers and a `getComposerGlobalPath` function to the `Valet` class to be able to get and use the global path of composer in the commands.

    -   Fixed the output for `composer global diagnose`, where it would only output 1 line of an info and no diagnostics. Fixed by outputting it to a file first and then reading the file before removing it.

    -   Fixed the output for `composer global outdated` to format as a HTML table in the output for copy. Additionally, made the terminal output more human readable depending on the command option used.

    -   Removed the unnecessary `runCommand` function, and used the `powershell` function of the `CommandLine` class instead. Powershell is used because it has native support for the `cat` function, which is the alias of `Get-Content` for getting file contents.

    -   Fixed the ability to copy to clipboard.

    -   Various changes to output for human readability or because the raw output wasn't good enough or had quirks.

-   Changed `log` command to use `cat` alias of the Powershell's `Get-Content` command instead of the `tail` command which only works in Git Bash.

    -   Also changed the options to that of Powershell's variants `-Tail` for how many lines and `-Wait` for following real time output.

    -   Swapped around the Valet command's options, and changed various descriptions.

    -   Changed the `runCommand` `CommandLine` function to allow real time output. The `setTimeout` is set to 0 to allow it to run for what should be "forever". Though this can't be tested for obvious reasons.

    -   Added a boolean param to all the other commandline functions that utilise the aforementioned function, so they can pass along and use the real time output if/when needed.

### Removed

-   Removed the deprecated PHP PowerShell files.
-   Removed unnecessary/redundant/duplicate code.
-   Removed the `--site` option from the `use` command that was added in https://github.com/cretueusebiu/valet-windows/pull/195, in favour of using the `isolate` command.
-   Removed the deprecated `getLinks` function in the `Site.php` file.
-   Removed the deprecated and unnecessary `publishParkedNginxConf`, `runOrDie`, `should_be_sudo`, `quietly`, `quietlyAsUser` functions.
-   Removed the unsupported `trust` command.
-   Removed the hardcoded ngrok options from the `share` command in favour of the new `--options` option.
-   Removed the `echo` from the `trustCa` function that was in the PR code from https://github.com/cretueusebiu/valet-windows/pull/179
-   Removed various outputs to fully streamline the progressbar UI and prevent multiple progressbars in the output because of multiple infos interrupting it.
-   Removed the ability to download PHP via an internal PowerShell script (`php.ps1`), because keeping it updated with the current versions of PHP and deprecating it's ancestor versions is impractical. Deleted the file and all related PHP `.ini`s.
-   Removed and deleted the unused and outdated tests, `.dockerignore`, `phpunit.xml` config files, and the related composer dependencies and scripts.
-   Removed the deprecated and unused legacy home path code, inline with the Mac version.
-   Removed the obsolete `domain` alias for `tld` command.

### Fixed

-   Fixed securing sites with an SSL/TLS certificate, both normally and when proxies are added by adding the localhost IP address to the Nginx conf listen directives. (PR by @RohanSakhale in https://github.com/cretueusebiu/valet-windows/pull/208).
-   Fixed a bug where sometimes the link won't be unlinked under certain conditions. In accordance with [official PHP guidelines of the `unlink()` function](https://www.php.net/manual/en/function.unlink.php), the function `rmdir()` fixes this issue to remove symlink directories.

    ###### From PHP `unlink()` docs:

    > If the file is a symlink, the symlink will be deleted. On Windows, to delete a symlink to a directory, rmdir() has to be used instead.

-   Fixed Nginx `lint` function to properly check the confs for errors.
-   Fixed filesystem `copy` function to use the `@` operator to suppress pr-error messages that occur in PHP internally. And added an inline `error` function if `copy` fails. We do this, so that we can construct proper meaningful error output for debugging.
-   Partial fix for a possible bug where if a site is using a framework that sets a site URL environment variable in a `.env` file such as the `WP_HOME` for Laravel Bedrock, then when trying to request the site and the TLD is different from the one set in Valet, then the site automatically redirects to use the URL from the environment variable. This ends in Valet returning a 404 error, because as far as Valet is concerned it's not valid site. This then results in the response being cached by the browser and keeps requesting the cached version of the site even if the TLD has been changed to match.

    Example: `WP_HOME='http://mySite.test'`, Valet is set to use the `dev` TLD and gets a request to `http://mySite.dev`, the site will auto redirect to `http://mySite.test`. 404 Not Found error appears. The response from `http://mySite.test` is cached by browsers. Valet is changed to use `test` TLD, and gets a request for `http://mySite.test`. The previously cached error response is served.

    This partial fix adds `no cache` headers to Nginx configuration files to try and prevent the browsers caching sites at all.

-   Fixed `services` command to correctly loop through and check all PHP services.
-   Fixed `fetch-share-url` command by:

    -   Replacing the outdated `nategood/httpful` composer dependency with `guzzlehttp/guzzle` for REST API requests to get the current ngrok tunnel public URL, and copy it to the clipboard.
    -   Changing the `findHttpTunnelUrl` function to use array bracket notation.
    -   Changing the `domain` argument to `site` to properly reference the site without getting confused with the ngrok `--domain`.
    -   Added a command alias: `url`.

-   Fixed `on-latest-version` command to use Guzzle and added a new composer dependency `composer/ca-bundle` to find and use the TLS CA bundle in order to verify the TLS/SSL certificate of the requesting website/API. Otherwise, Guzzle spits out a cURL error. (Thanks to this [StackOverflow Answer](https://stackoverflow.com/a/53823135/2358222).)

    Also added a command alias: `latest`.

-   Fixed `ngrok` command to accept options/flags, using the new `--options` option. See the [docs](https://github.com/yCodeTech/valet-windows/blob/master/README.md#notes-for-all---options) for information on how this works.
-   Fixed `php:remove` command to enable it to remove PHP by specifying it's version; by adding a `phpVersion` argument and changing the `path` argument to an option (`--path`), making `phpVersion` the main way to remove instead.
-   Fixed `start` command by removing the whole functionality and utilise Silly's `runCommand` to run the `restart` command instead, so they're effectively sharing the same function. This is because it was unnecessary duplicated code.
-   Fixed lack of output colouring when using PHP `passthru` function by adding a 3rd party binary, [Ansicon](https://github.com/adoxa/ansicon), along with a new class with `install`/`uninstall` functions and added the function calls to the Valet `install`/`uninstall` commands respectively.

    For whatever reason, the `passthru` function loses the output colourings, therefore the visual meaning is lost. What Ansicon does is injects code into the active and new terminals to ensure ANSI escape sequences (that are interpreted from the HTML-like tags `<fg=red></>`) are correctly rendered.

-   Fixed a bug where a non-secured isolated site's server port was being replaced with the PHP port and was being served with the default server instead of the isolated server.

    So instead of the usual port 80: `127.0.0.1:80` in the site's isolated config file, nginx was actually listening for the site on port 9002: `127.0.0.1:9002`, which is the port where PHP 7.4 was configured to be hosted. This confused nginx because it couldn't find port 80 in the site's config file, so it used the default server instead, in which the default PHP version was set as 8.1 on the port 9001, and thus serving the site under the wrong PHP version.

    Fixed by removing the `preg_replace` in the `replacePhpVersionInSiteConf` function of the `Site` class. (This doesn't affect the PHP isolation and still works as intended.)

-   Fixed an oversight while changing the TLD by allowing isolated sites TLD to be changed. Previously, if there is an isolated site, changing the TLD wouldn't change the isolated site's conf file, thus leaving it as the old TLD. This fix adds a `reisolateForNewTld` function to unisolate the old TLD site and reisolate the new TLD site. Also works for sites that are both isolated and secured.

-   Fixed `unlink` command to properly get the site from the current working directory if no `name` was supplied, by using the previously `private`, now `public` `getLinkNameByCurrentDir()` function. Also changed the error message in the latter function to include the multiple linked names.

-   Fixed `stop` function of the `PhpCgi` class to only `stop` the PHP CGI or PHP CGI Xdebug if it's installed.

    This prevents errors occurring when `uninstall`ing Valet. It would try to `stop` Xdebug services for all PHP versions available to Valet, even though all Xdebug services may not be installed.

-   Fixed `unsecure --all` to `exit` the script if there are no sites to unsecure, otherwise nginx would still run afterwards, which is not needed.

-   Fixed `uninstall` command to only unsecure sites if they are any secured sites. Also prevented the `unsecureAll` function from exiting the script if the call came from `uninstall`. Also added an option shortcut `-p` for `--purge-config`.

-   Fixed `install` command to detect if Valet is already installed. Checks the services to see if they are running, if they are, Valet will ask a question whether to reinstall Valet or not. Also changed `services` function (which this fix uses) to disable the progressbar when it's called from `install`.

-   Fixed the Xdebug `failed to restart` error in the `restart` function of the `PhpCgi` class (of which `PhpCgiXdebug` class shares) by adding a check to see if the Xdebug service is installed, it will only restart the service if it's installed.

    This is because previously, if there is a version of Xdebug installed and other versions of PHP didn't have their corresponding Xdebug installed, and the `restart` function is ran, Valet would then spit out an error in complaint of the Xdebug PHP version failing to restart, because it's not installed.

-   Fixed the `default` PHP configuration on `install`, by allowing Valet to only set the default PHP if they key doesn't exist or if it's `null`.

    This is because previously, upon installing Valet, if the config file already exists with a default PHP set, Valet will always reset the default no matter what, even if Valet is set to use a version other than the version it finds from the `where php` CMD command, which gets it from Windows PATH (eg. default in config: 7.4.33, Valet finds: 8.1.8; Valet resets the default back to 8.1.8).

    -   Also fixed a bug in relation to this flaw... When adding the default PHP, Valet would sometimes fail to get the PHP path because of the inconsistent capitalisation of the drive letter in the PHP path. If the path's drive letter is a lowercase, it would fail because of the strict comparison between the former and Valet's retrieval of the uppercase letter from `where php` (eg. config: c:/php/8.1; Valet retrieves: C:/php/8.1).

    Therefore, the comparison returns `null` and sets the default as `null`. Then further installation scripts would stop because of the `Cannot find PHP [null] in the list` error.

    Valet will now convert the drive letter to a lowercase via the native `lcfirst` PHP function in both `addDefaultPhp` function of `Configuration` class and in the `php:add` command. So now the default PHP should never be `null`.

-   Fixed https://github.com/yCodeTech/valet-windows/issues/3 where upon a fresh installation of Valet with no config.json, it would try to read the config even if it's not yet created and spits out an "Failed to open stream: No such file" error. Fixed by adding a check to see if the file exists in the `read` function of the `Configuration` class (fix PR by @hemant-kr-meena in https://github.com/yCodeTech/valet-windows/pull/4).

## For previous versions prior to this repository, please see [cretueusebiu/valet-windows](https://github.com/cretueusebiu/valet-windows), of which this is an indirect fork of.
