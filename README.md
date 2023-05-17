<p align="center"><img src="./laravel_valet_windows_3_logo.svg" width="50%"></p>

<p align="center">
<a href="https://github.com/cretueusebiu/valet-windows/actions?query=workflow%3Atests"><img src="https://github.com/cretueusebiu/valet-windows/workflows/Tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/cretueusebiu/valet-windows"><img src="https://poser.pugx.org/cretueusebiu/valet-windows/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/cretueusebiu/valet-windows"><img src="https://poser.pugx.org/cretueusebiu/valet-windows/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/cretueusebiu/valet-windows"><img src="https://poser.pugx.org/cretueusebiu/valet-windows/license.svg" alt="License"></a>
</p>

<p align="center">Windows port of the popular development environment <a href="https://github.com/laravel/valet">Laravel Valet</a>.</p>

## Introduction

Valet is a Laravel development environment for Windows. No Vagrant, no `/etc/hosts` file. You can even share your sites publicly using local tunnels. _Yeah, we like it too._

Laravel Valet configures your Windows to always run Nginx in the background when your machine starts. Then, using [Acrylic DNS](http://mayakron.altervista.org/wikibase/show.php?id=AcrylicHome), Valet proxies all requests on the `*.test` domain to point to sites installed on your local machine.

## Documentation

Before installation, make sure that no other programs such as Apache or Nginx are binding to your local machine's port 80. <br> Also make sure to open your preferred terminal (Windows Terminal, CMD, Git Bash, PowerShell, etc.) as Administrator.

- If you don't have PHP installed, open PowerShell (3.0+) as Administrator and run:

```bash
# PHP 8.1
Set-ExecutionPolicy RemoteSigned -Scope Process; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri "https://github.com/ycodetech/valet-windows/raw/master/bin/php.ps1" -OutFile $env:temp\php.ps1; .$env:temp\php.ps1 "8.1"

# PHP 8.0
Set-ExecutionPolicy RemoteSigned -Scope Process; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri "https://github.com/ycodetech/valet-windows/raw/master/bin/php.ps1" -OutFile $env:temp\php.ps1; .$env:temp\php.ps1 "8.0"

# PHP 7.4
Set-ExecutionPolicy RemoteSigned -Scope Process; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri "https://github.com/ycodetech/valet-windows/raw/master/bin/php.ps1" -OutFile $env:temp\php.ps1; .$env:temp\php.ps1 "7.4"
```

> This script will download and install PHP for you and add it to your environment path variable. PowerShell is only required for this step.

- If you don't have Composer installed, make sure to [install](https://getcomposer.org/Composer-Setup.exe) it.

- Install Valet with Composer via `composer global require ycodetech/valet-windows`.

- Run the `valet install` command. This will configure and install Valet and register Valet's daemon to launch when your system starts. Once installed, run `valet start` command to start the daemon.

- If you're installing on Windows 10/11, you may need to [manually configure](https://mayakron.altervista.org/support/acrylic/Windows10Configuration.htm) Windows to use the Acrylic DNS proxy.

Valet will automatically start its daemon each time your machine boots. There is no need to run `valet start` or `valet install` ever again once the initial Valet installation is complete.

For more please refer to the official documentation on the [Laravel website](https://laravel.com/docs/8.x/valet#serving-sites).

## Known Issues

- WSL2 distros fail because of Acrylic DNS Proxy ([microsoft/wsl#4929](https://github.com/microsoft/WSL/issues/4929)). Use `valet stop`, start WSL2 then `valet start`.
- The PHP-CGI process uses port 9001. If it's already used change it in `~/.config/valet/config.json` and run `valet install` again.
- When sharing sites the url will not be copied to the clipboard.
- You must run the `valet` commands from the drive where Valet is installed, except for park and link. See [#12](https://github.com/cretueusebiu/valet-windows/issues/12#issuecomment-283111834).
- If your machine is not connected to the internet you'll have to manually add the domains in your `hosts` file or you can install the [Microsoft Loopback Adapter](https://docs.microsoft.com/en-us/troubleshoot/windows-server/networking/install-microsoft-loopback-adapter) as this simulates an active local network interface that Valet can bind too.
- When trying to run valet on PHP 7.4 and you get this error:

  > Composer detected issues in your platform:
  >
  > Your Composer dependencies require a PHP version ">= 8.1.0". You are running 7.4.33.
  >
  > PHP Fatal error: Composer detected issues in your platform: Your Composer dependencies require a PHP version ">= 8.1.0". You are running 7.4.33. in C:\Users\YourName\AppData\Roaming\Composer\vendor\composer\platform_check.php on line 24

  It means that a dependency of Valet's dependencies requires 8.1. You can rectify this error by running `composer global update` while on 7.4, and composer will downgrade any global dependencies to versions that will work on 7.4. See this [Stack Overflow answer](https://stackoverflow.com/a/75080139/2358222).

  NOTE #1: This will of course downgrade all global packages. Depending on the packages, it may break some things. If you just want to downgrade valet dependencies, then you can specify the valet namespace. `composer global update ycodetech/valet-windows`.

  NOTE #2: It's recommended to use PHP 8.1 anyway, downgrading will mean some things may break or cause visual glitches in the terminal output. So downgrade at your own risk.

## Xdebug

To enable a debugging session you can use [Xdebug helper](https://chrome.google.com/webstore/detail/xdebug-helper/eadndfjplgieldjbigjakmdgkmoaaaoc) or set a cookie with the name `XDEBUG_SESSION`.

## Testing

Run the unit tests with:

```bash
composer test-unit
```

Before running the integration tests for the first time, you must build the Docker container with:

```bash
composer build-docker
```

Next, you can run the integration tests with:

```bash
composer test-integration
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## License

Laravel Valet is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
