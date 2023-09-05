<p align="center"><img src="./laravel_valet_windows_3_logo.svg" style="width:500px; background: none;"></p>

<p align="center">
<a href="https://packagist.org/packages/ycodetech/valet-windows"><img src="https://poser.pugx.org/ycodetech/valet-windows/downloads" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/ycodetech/valet-windows"><img src="https://poser.pugx.org/ycodetech/valet-windows/v" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/ycodetech/valet-windows"><img src="https://poser.pugx.org/ycodetech/valet-windows/v/unstable" alt="Latest Unstable Version"></a>
<a href="https://packagist.org/packages/ycodetech/valet-windows"><img src="https://poser.pugx.org/ycodetech/valet-windows/license" alt="License"></a>
</p>

<p align="center">This is a Windows port of the popular Mac development environment <a href="https://github.com/laravel/valet">Laravel Valet</a>.</p>
<p align="center">Laravel Valet <i>Windows</i> 3 is a much needed updated fork of <a href="https://github.com/cretueusebiu/valet-windows">cretueusebiu/valet-windows</a>, with lots of improvements and new commands. This version hopes to achieve as much parity as possible with the Mac version.</p>

## !! This is in active development, official release coming soon !!

### !! The dev release can now be installed via `composer global require ycodetech/valet-windows` !!

<p align="center"><img src="./composer_laravel_valet_windows_3_logo.svg" style="width:400px; background: none;"></p>

> **Warning** **If you're coming from <a href="https://github.com/cretueusebiu/valet-windows">cretueusebiu/valet-windows</a>, then you need to make sure to fully uninstall it from your computer, deleting all configs, and removing from composer with `composer global remove cretueusebiu/valet-windows`, before installing this 3.0 version.**

<br>

[Introduction](#introduction) | [Documentation](#documentation) | [Commands](#commands) | [Command Parity Checker](#command-parity-checker) | [Commands Not Supported](#commands-not-supported) | [Known Issues](#known-issues) | [Xdebug Installation](#xdebug-installation) | [Contributions](#contributions)

---

<table>
  <tr>
    <th>Commands Section</th>
    <td ><a href="#installinguninstalling--startingstopping">Installing/Uninstalling & Starting/Stopping</a></td>
    <td ><a href="#php-services">PHP Services</a></td>
    <td align="center"><a href="#using-php-versions">Multi-PHP Versions and Securing</a></td>
    <td align="center"><a href="#parked-linked-proxies-and-sites">Parked, Linked, Proxies and Sites</a></td>
    <td align="center"><a href="#sharing">Sharing</a></td>
    <td align="center"><a href="#other-commands">Other Commands</a></td>
  </tr>
  <tr>
    <th>Command</th>
    <td><a href="#install">install</a></td>
    <td><a href="#phpadd">php:add</a></td>
    <td align="center"><a href="#use">use</a></td>
    <td align="center"><a href="#park">park</a></td>
		<td align="center"><a href="#share">share</a></td>
		<td align="center"><a href="#tld">tld</a></td>
  </tr>

  <tr>
  <th></th>
    <td><a href="#sudo">sudo</a></td>
    <td><a href="#phpremove">php:remove</a></td>
    <td align="center"><a href="#isolate">isolate</a></td>
    <td align="center"><a href="#parked">parked</a></td>
    <td align="center"><a href="#authset-ngrok-token">auth|set-ngrok-token</a></td>
    <td align="center"><a href="#which">which</a></td>
  </tr>

  <tr>
  <th></th>
    <td><a href="#start">start</a></td>
    <td><a href="#phpinstall">php:install</a></td>
    <td align="center"><a href="#isolated">isolated</a></td>
    <td align="center"><a href="#unparkforget">unpark|forget</a></td>
    <td align="center"><a href="#urlfetch-share-url">url|fetch-share-url</a></td>
    <td align="center"><a href="#paths">paths</a></td>

  </tr>
  <tr>
  <th></th>
    <td><a href="#restart">restart</a></td>
    <td><a href="#phpuninstall">php:uninstall</a></td>
    <td align="center"><a href="#unisolate">unisolate</a></td>
    <td align="center"><a href="#link">link</a></td>
    <td align="center"><a href="#ngrok">ngrok</a></td>
		<td align="center"><a href="#open">open</a></td>

  </tr>
  <tr>
  <th></th>
    <td><a href="#stop">stop</a></td>
		<td><a href="#phplist">php:list</a></td>
		<td align="center"><a href="#secure">secure</a></td>
		<td align="center"><a href="#links">links</a></td>
    <td></td>
		<td align="center"><a href="#lateston-latest-version">latest|on-latest-version</a></td>

  </tr>

  <tr>
  <th></th>
    <td><a href="#uninstall">uninstall</a></td>
		<td><a href="#phpwhich">php:which</a></td>
		<td align="center"><a href="#secured">secured</a></td>
		<td align="center"><a href="#unlink">unlink</a></td>
		<td></td>
		<td align="center"><a href="#log">log</a></td>

  </tr>

  <tr>
  <th></th>
    <td></td>
    <td><a href="#xdebuginstall">xdebug:install</a></td>
		<td align="center"><a href="#unsecure">unsecure</a></td>
		<td align="center"><a href="#proxy">proxy</a></td>
    <td></td>
    <td align="center"><a href="#services">services</a></td>

  </tr>

  <tr>
  <th></th>
    <td></td>
    <td><a href="#xdebuguninstall">xdebug:uninstall</a></td>
    <td></td>
		<td align="center"><a href="#proxies">proxies</a></td>
    <td></td>
		<td align="center"><a href="#directory-listing">directory-listing</a></td>
  </tr>

  <tr>
  <th></th>
    <td></td>
    <td></td>
    <td></td>
		<td align="center"><a href="#unproxy">unproxy</a></td>
    <td></td>
		<td align="center"><a href="#diagnose">diagnose</a></td>
  </tr>

  <tr>
  <th></th>
    <td></td>
    <td></td>
    <td></td>
		<td align="center"><a href="#sites">sites</a></td>
    <td></td>
		<td></td>
  </tr>
</table>

## Introduction

Valet is a Laravel development environment for Windows. No Vagrant, no `/etc/hosts` file. You can even share your sites publicly using local tunnels. _Yeah, we like it too._

Laravel Valet configures your Windows to always run Nginx in the background when your machine starts. Then, using [Acrylic DNS](http://mayakron.altervista.org/wikibase/show.php?id=AcrylicHome), Valet proxies all requests on the `*.test` domain (aka tld) to point to sites installed on your local machine.

This is 3.0 of Valet Windows, branded under the name _Laravel Valet Windows 3_, and is a much needed updated fork of <a href="https://github.com/cretueusebiu/valet-windows">cretueusebiu/valet-windows</a>. It introduces lots of improvements, new commands, and hopes to achieve as much parity as possible with the original Mac version.

## Documentation

Before installation, make sure that no other programs such as Apache or Nginx are binding to your local machine's port 80. If XAMPP or similar is installed make sure they don't have Windows services installed and change their ports.

Also make sure to open your preferred terminal (Windows Terminal, CMD, Git Bash, PowerShell, etc.) as Administrator. You can use VS Code integrated terminal, but if VS Code isn't opened as Administrator, then a bunch of User Account Control (UAC) pop ups will appear in order to give access to Valet. You can also use a non Administrator terminal without the popups, you'll just need to use Valet's [`sudo`](#sudo) command before any other Valet commands.

NOTE: Laravel Valet Windows 3 is **developed and tested to run on Windows 10**. In theory it should run on Windows 11 and up, but there's no guarantee. Testers and contributors are always welcome though.

---

- If you don't have PHP installed, make sure to [install](https://windows.php.net/download) it.

  Download the Zip file and unzip into `C:/php/`. You may use Thread Safe (TS), but Non-Thread Safe (NTS) is better for using PHP on the FastCGI protocol, which Valet uses.

  > For NTS binaries the widespread use case is interaction with a web server through the FastCGI protocol, utilizing no multithreading (but also for example CLI).

- If you don't have Composer installed, make sure to [install](https://getcomposer.org/doc/00-intro.md#installation-windows) it.

- Install Valet with Composer via `composer global require ycodetech/valet-windows`.

  <p align="center"><img src="./composer_laravel_valet_windows_3_logo.svg" style="width:400px; background: none;"></p>

  > **Warning** **If you're coming from <a href="https://github.com/cretueusebiu/valet-windows">cretueusebiu/valet-windows</a>, then you need to make sure to fully uninstall it from your computer, deleting all configs, and removing from composer with `composer global remove cretueusebiu/valet-windows`, before installing this 3.0 version.**

- Install Valet by running the `valet install` command, or alternatively `valet sudo install` with administrator elevation. This will configure and install Valet and register Valet's daemon to launch when your system starts. Once installed, Valet will automatically start it's services.

- If you're installing on Windows 10/11, you may need to [manually configure](https://mayakron.altervista.org/support/acrylic/Windows10Configuration.htm) Windows to use the [Acrylic DNS Proxy](https://mayakron.altervista.org/support/acrylic/Home.htm).

Valet will automatically start its daemon each time your machine boots. There is no need to run `valet start` or `valet install` ever again once the initial Valet installation is complete.

## Commands

### Installing/Uninstalling & Starting/Stopping

##### install

```
install            Install Valet's services and configs
Install Valet's services and configs, and auto start Valet.
        [--xdebug] Optionally, install Xdebug for PHP.
```

```console
$ valet install
$ valet install --xdebug
Valet installed and started successfully!
```

This installs all Valet services:

- Nginx
- PHP CGI
- PHP Xdebug CGI [optional]
- Acrylic DNS
- Ansicon

And it's configs in `C:\Users\Username\.config\valet`.

Once complete, Valet will automatically start the services.

###### Note: If `install` is ran again when it's already installed, Valet will ask if you want to proceed to reinstall.

###### install --xdebug

`--xdebug` is a boolean option to optionally install Xdebug for PHP. If the option is present, it's `true`, otherwise `false`.

##### sudo

```
sudo [valetCommand]      A sudo-like command to use Valet commands with elevated privileges.
     [-o|--valetOptions] Specify Valet command options/flags.
```

```console
$ valet sudo install
```

`sudo` is a Windows equivalent of the Mac command utilty of the same name, provided by [gsudo](https://github.com/gerardog/gsudo). The command allows you to pass through Valet commands to gsudo, gsudo will then elevate the command to use the highest system administrator privileges, without the need for multiple User Account Control (UAC) popups...

gsudo only requires 1 UAC popup to enable elevation (per usage), and then the passed Valet command, it's arguments, values and options will be executed as the system with no further UACs.

###### valetCommand

`valetCommand` is the Valet command, plus it's argument's values that you wish to run. It is a string array separated by spaces.

```console
$ valet sudo isolate 7.4
```

In the example above, `isolate` is the command name and `7.4` is the argument value.

When specifying the Valet command, you can pass the `valet` CLI keyword before the command as you would normally, but this is optional. If it's omitted, Valet will add it automatically. It's preferred to omit the keyword as it's cleaner.

```
$ valet sudo valet isolate 7.4
```

###### --valetOptions

`--valetOptions` (shortcut `-o`) [optional] is the Valet options/flags for a Valet command. It is a string, but multiple options can be specified. Please see the [important notes](#notes-for-all---options) about this option.

```console
$ valet sudo link mysitename -valetOptions=isolate//secure
$ valet sudo link mysitename -o isolate//secure
```

##### start

```
start            Starts Valet's services
       [service] Optionally, specify a particular service to start [acrylic, nginx, php, xdebug]
```

```console
$ valet start
Valet services have been started.

$ valet start nginx
Nginx has been started.
```

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

##### restart

```
restart            Restarts Valet's services
         [service] Optionally, specify a particular service to restart [acrylic, nginx, php, xdebug]
```

```console
$ valet restart
Valet services have been restarted.

$ valet restart nginx
Nginx has been restarted.
```

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

##### stop

```
stop            Stops Valet's services
      [service] Optionally, specify a particular service to stop [acrylic, nginx, php, xdebug]
```

```console
$ valet stop
Valet services have been stopped.

$ valet start nginx
Nginx has been stopped.
```

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

##### uninstall

```
uninstall                      Uninstalls Valet's services
           [--force]           Optionally force uninstallation without a confirmation question
           [-p|--purge-config] Optionally purge and remove all Valet configs
```

```console
$ valet uninstall
Are you sure you want to proceed? yes/no
$ yes
Valet has been removed from your system.
```

This completely stops and uninstalls all of Valet's services.

You will also need to `uninstall` Valet if you are wanting to update Valet via Composer (`composer global update ycodetech/valet-windows`), just to make sure Composer can remove and update relevant files without error.

###### --force

`--force` is to optionally force an uninstallation without Valet asking confirmation.

```console
$ valet uninstall --force
Valet has been removed from your system.
```

###### --purge-config

`--purge-config` (shortcut `-p`) is to optionally purge and remove all Valet's configs. This should be used if Valet is no longer required and it won't be installed again.

```console
$ valet uninstall --purge-config
$ valet uninstall -p
Are you sure you want to proceed? yes/no
$ yes
Valet has been uninstalled from your system, and purged all configs.
```

### PHP Services

##### php:add

```
php:add [path]     Add PHP by specifying a path
        [--xdebug] Optionally, install Xdebug
```

```console
$ valet php:add "C:\php\7.4"
PHP 7.4.33 from C:\php\7.4 has been added.
```

###### **Note:** When adding PHP, the full version number (eg. 7.4.33) will be extracted and an alias (eg. 7.4) will be generated. Either of these can be used in other commands.

###### Furthermore, the details of the versions will be written to the config in a natural decending order that adheres to decimals. This means that when two minor versions (like 8.1.8 and 8.1.18) of an alias (8.1) are added, and the default PHP is then set to use the alias, then Valet will use the most recent version of the alias, which in this case would be 8.1.18.

###### php:add --xdebug

`--xdebug` is a boolean option to optionally install Xdebug for the PHP being added. If the option is present, it's `true`, otherwise `false`.

```console
$ valet php:add "C:\php\7.4" --xdebug
Installing Xdebug for 7.4.33...
PHP 7.4.33 from C:\php\7.4 has been added.

```

##### php:remove

```
php:remove [phpVersion] Remove PHP by specifying it's version
           [--path=]    Optionally specify by path
```

Both the full version and the alias version works:

```console
$ valet php:remove 7.4.33
$ valet php:remove 7.4
PHP 7.4.33 from c:\php\7.4 has been removed.
```

###### --path

Instead of using the version number, you can specify the PHP by it's path.

```console
$ valet php:remove --path="C:\php\7.4"
PHP 7.4.33 from c:\php\7.4 has been removed.
```

###### Note: If Xdebug is installed for the PHP being removed, then Valet will also remove Xdebug for that version too.

##### php:install

```
php:install   Reinstall all PHP services from [valet php:list]
```

```console
$ valet php:install
Reinstalling PHP services...
```

`php:install` Installs the PHP CGI services for the versions listed in Valet with [`php:list`](#phplist). If they are already installed, Valet uninstalls them first and then reinstalls them.

##### php:uninstall

```
php:uninstall   Uninstall all PHP services from [valet php:list]
```

```console
$ valet php:uninstall
Uninstalling PHP services...
```

`php:uninstall` Uninstalls the PHP CGI services for the versions listed in Valet with [`php:list`](#phplist).

##### php:list

```
php:list   List all PHP versions and services
```

List the PHP versions installed in Valet.

```console
$ valet php:list
Listing PHP services...
┌─────────┬───────────────┬────────────┬──────┬─────────────┬─────────┐
| Version | Version Alias | Path       | Port | xDebug Port | Default |
├─────────┼───────────────┼────────────┼──────┼─────────────┼─────────┤
| 8.1.8   | 8.1           | C:\php\8.1 | 9006 | 9106        | X       |
├─────────┼───────────────┼────────────┼──────┼─────────────┼─────────┤
| 7.4.33  | 7.4           | C:\php\7.4 | 9004 | 9104        |         |
└─────────┴───────────────┴────────────┴──────┴─────────────┴─────────┘
```

##### php:which

```
php:which         Determine which PHP version the current working directory is using
           [site] Optionally, specify a site
```

```console
$ valet php:which
The current working directory site1 is using PHP 7.4.33 (isolated)

$ valet php:which site2
The specified site site2 is using PHP 8.1.8 (default)
```

##### xdebug:install

```
xdebug:install               Install Xdebug services for all PHP versions from [valet php:list]
                [phpVersion] Optionally, specify one particular PHP version of Xdebug to install
```

`xdebug:install` installs an Xdebug service for all PHP versions listed in Valet with [`php:list`](#phplist).

```console
$ valet xdebug:install
Installing Xdebug services...
Installed Xdebug for PHP 7.4.33, 8.1.8
```

Valet only installs an Xdebug PHP CGI service on a separate port to work along side the PHP CGI service, it doesn't install Xdebug itself. Please read the [Xdebug Installation](#xdebug-installation) for further information.

You can optionally install Xdebug for one specific PHP version using the `phpVersion` argument.

```console
$ valet xdebug:install 7.4
Installing Xdebug services...
Installed Xdebug for PHP 7.4.33
```

###### Note: If Xdebug for the supplied PHP version is already installed, Valet will ask if you want it reinstalling.

##### xdebug:uninstall

```
xdebug:uninstall               Uninstall all Xdebug services
                  [phpVersion] Optionally, specify one particular PHP version of Xdebug to uninstall
```

```console
$ valet xdebug:uninstall
Xdebug services uninstalled.
```

You can optionally uninstall Xdebug for one specific PHP version using the `phpVersion` argument.

```console
$ valet xdebug:uninstall 7.4
Installing Xdebug services...
Installed Xdebug for PHP 7.4.33
```

### Using PHP versions

##### use

```
use  [phpVersion]  Change the default PHP version used by Valet. Either specify the full version or the alias
```

```console
$ valet use 8.1
Setting the default PHP version to [8.1].
Valet is now using 8.1.18.

$ valet use 8.1.8
Setting the default PHP version to [8.1.8].
Valet is now using 8.1.8.
```

###### Note: If using the alias, and multiple versions of 8.1 are available eg. 8.1.8 and 8.1.18, then the most latest version will be used eg. 8.1.18.

##### isolate

```
isolate   [phpVersion]  Isolates the current working directory to a specific PHP version
          [--site=]     Optionally specify the site instead of the current working directory
```

###### Note: You can isolate 1 or more sites at a time. Just pass the `--site` option for each of the sites you wish to isolate to the same PHP version.

```console
$ cd /d/sites/my_site
$ valet isolate 7.4
Isolating the current working directory...
The site [my_site] is now using 7.4.

$ valet isolate 7.4 --site=another_site
The site [another_site] is now using 7.4.

$ valet isolate 7.4 --site=site1 --site=site2 --site=site3
The site [site1] is now using 7.4.
The site [site2] is now using 7.4.
The site [site3] is now using 7.4.
```

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

##### isolated

```
isolated  List all isolated sites
```

```console
$ valet isolated
┌──────────┬────────┐
| Site     | PHP    |
├──────────┼────────┤
| site1    | 7.4.33 |
├──────────┼────────|
| my_site  | 7.4.33 |
└──────────┴────────┘
```

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

##### unisolate

```
unisolate            Removes [unisolates] the current working directory
          [--site=]  Optionally specify the site instead of the current working directory
          [--all]    Optionally removes all isolated sites
```

```console
$ cd /d/sites/my_site
$ valet unisolate
Unisolating the current working directory...
The site [my_site] is now using the default PHP version.

$ valet unisolate --site=my_site
The site [my_site] is now using the default PHP version.
```

###### unisolate --all

`--all` is a boolean option to optionally unisolate all the currently isolated sites. If the option is present, it's `true`, otherwise `false`.

```console
$ valet unisolate --all
The site [my_site] is now using the default PHP version.
The site [site1] is now using the default PHP version.
```

##### secure

```
secure          Secure the current working directory with a trusted TLS certificate
        [site]  Optionally specify the site instead of the current working directory
```

Secures a site with a trusted self-signed TLS certificate and serves the site on the `https` protocol.

```console
$ cd /d/sites/site1
$ valet secure
The [site1.test] site has been secured with a fresh TLS certificate and will now be served over HTTPS.

$ valet secure site1
The [site1.test] site has been secured with a fresh TLS certificate and will now be served over HTTPS.
```

###### Note: The secure command (or secure option in any other commands) need to be used in an admin privileged/elevated terminal. Either open a terminal as administrator or use the [`sudo`](#sudo) command.

##### secured

```
secured  List all secured sites
```

```console
$ valet secured
┌──────────────┐
| Site         |
├──────────────┤
| site1.test   |
├──────────────┤
| my_site.test |
└──────────────┘
```

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

##### unsecure

```
unsecure        Unsecure the current working directory
        [site]  Optionally specify the site instead of the current working directory
        [--all] Optionally unsecure all secured sites
```

Unsecures a site by removing it's TLS certificate and serves the site on the `http` protocol.

```console
$ cd /d/sites/site1
$ valet unsecure
The [site1.test] site has been unsecured and will now be served over HTTP.

$ valet secure site1
The [site1.test] site has been unsecured and will now be served over HTTP.
```

###### unsecure --all

`--all` is a boolean option to optionally unsecure all the currently secured sites. If the option is present, it's `true`, otherwise `false`.

```console
$ valet unsecure --all
Unsecured all sites.
```

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

### Parked, Linked, Proxies and Sites

##### park

```
park         Registers the current working directory to automatically serve sub-directories as sites
      [path] Optionally, specify a path
```

```console
$ cd /d/sites
$ valet park
This directory has been registered to Valet and all sub-directories will be accessible as sites.

$ valet park d/sites
The [d/sites] directory has been registered to Valet and all sub-directories will be accessible as sites.
```

`park` registers a directory that contains all your sites to Valet. Once the directory has been _`parked`_, Valet will automatically serve all sub-directories as sites, accessible in the web browser. They serve in the form of `http://<directory-name>.test`

To view all registered directories, use the [`paths`](#paths) command.

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version. For more information visit the <a href="https://laravel.com/docs/10.x/valet#the-park-command">Laravel Valet docs</a>.

##### parked

```
parked  List all the current sites within parked paths
```

###### Note: If there's a parked site that is also a symbolic linked site, then it will also output the linked site name (aka alias) and it's URL (aka alias URL).

```console
$ valet parked
┌───────────────────────────────────────────────┐
|      Site: site1                              |
|     Alias:                                    |
|       SSL:                                    |
|       PHP: 7.4.33 (isolated)                  |
|       URL: http://site1.test                  |
| Alias URL:                                    |
|      Path: D:\_Sites\site1                    |
├───────────────────────────────────────────────┤
|      Site: another site                       |
|     Alias: another_site_renamed               |
|       SSL:                                    |
|       PHP: 8.1.18 (default)                   |
|       URL: http://another site.test           |
| Alias URL: http://another_site_renamed.test   |
|      Path: D:\_Sites\another site             |
└───────────────────────────────────────────────┘
```

##### unpark|forget

```
unpark | forget         Remove the current working directory from Valet
                 [path] Optionally, specify a path
```

To stop auto-serving sub-directories as sites, run the `forget` command to _`forget`_ the directory.

`unpark` is a command alias.

```console
$ cd /d/sites
$ valet forget
$ valet unpark
This directory has been removed from Valet.

$ valet forget d/sites
$ valet unpark d/sites
The [d/sites] directory has been removed from Valet.
```

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

##### link

```
link                Register the current working directory as a symbolic link
      [name]        Optionally specify a new name to be linked as
      [--secure]    Optionally secure the site
      [--isolate=]  Optionally isolate the site to a specified PHP version
```

`link` is another way to serve directories as sites, except it serves one singular site in a directory rather than the whole directory. It does this by creating a symbolic link inside the `/.config/valet/Sites` directory, of which is a _`parked`_ directory on Valet installation.

Serving the current working directory as a site:

```console
$ cd /d/sites/site1
$ valet link
A [site1] symbolic link has been created in [C:/Users/Username/.config/valet/Sites/site1].
```

###### name

If you wish to serve the site under a different name to that of the directory, just pass the optional `name` argument. This is most useful if the directory has spaces in the name, Valet doesn't URL encode spaces or any other non-URL safe characters, so the site won't work without changing it.

```console
$ cd /d/sites/my awesome site
$ valet link my_site_renamed
A [my_site_renamed] symbolic link has been created in [C:/Users/Username/.config/valet/Sites/my_site_renamed].
```

###### --secure

`--secure` option allows you to secure the site. It is boolean, so if it's present it's `true`, otherwise `false`.

```console
$ valet link cool_site --secure
A [cool_site] symbolic link has been created in [C:/Users/Username/.config/valet/Sites/cool_site].
The [cool_site.test] site has been secured with a fresh TLS certificate.
```

###### --isolate

`--isolate` option allows you to isolate the site to a specific PHP version. Pass it with a value.

```console
$ valet link cool_Site --isolate=7.4
A [cool_site] symbolic link has been created in [C:/Users/Username/.config/valet/Sites/cool_site].
The site [cool_site.test] is now using 7.4.
```

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version. For more information, please see the <a href="https://laravel.com/docs/10.x/valet#the-link-command">Laravel Valet docs</a>.

##### links

```
links  List all registered symbolic links
```

```console
$ valet links
┌─────────────────┬─────────┬──────────────────┬─────────────────────────────┬───────────────────────────────────────┐
| Site            | Secured | PHP              | URL                         | Path                                  |
├─────────────────┼─────────┼──────────────────┼─────────────────────────────┼───────────────────────────────────────┤
| my_site_renamed |         | 8.1.18 (default) | http://my_site_renamed.code | D:\_Sites\a_completely_different_name |
├─────────────────┼─────────┼──────────────────┼─────────────────────────────┼───────────────────────────────────────┤
| cool_site       | X       | 7.4.33 (isolated)| http://cool_site.code       | D:\_Sites\cool and awesome site       |
└─────────────────┴─────────┴──────────────────┴─────────────────────────────┴───────────────────────────────────────┘
```

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

##### unlink

```
unlink          Unlink the current working directory linked site
        [name]  Optionally specify the linked site name
```

`unlink` removes the current working directory's (cwd) symbolic linked site. Valet will find the linked site name using the cwd name and delete the symbolic link from the `/.config/valet/Sites/` directory.

```console
$ cd /d/sites/my site
$ valet unlink
The [cool_site] symbolic link has been removed.
```

###### name

`name` specifies the name of the symbolic link.

```console
$ valet unlink cool_site
The [cool_site] symbolic link has been removed.
```

###### Note: If the linked site is `secured`, Valet will unsecure it before removing.

```console
$ valet unlink cool_site
Unsecuring cool_site...
The [cool_site] symbolic link has been removed.
```

###### Note: If the linked site is `isolated`, Valet will unisolate it before removing.

```console
$ valet unlink cool_site
The site [cool_site] is now using the default PHP version.
The [cool_site] symbolic link has been removed.
```

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

##### proxy

```
proxy [site] [host]  Proxy a specified site to a specified host
      [--secure]     Optionally, secure with a trusted TLS certificate
```

`proxy` allows you to _`proxy`_ a Valet site to another service on your machine and send all traffic from the Valet site to the service.
You may also proxy multiple sites to 1 host by separating them with commas.

```console
$ valet proxy site1 http://127.0.0.1:9200
Valet will now proxy [http://site1.test] traffic to [http://127.0.0.1:9200]

$ valet proxy site1,site2,site3 https://127.0.0.1:9200
Valet will now proxy [http://site1.test] traffic to [http://127.0.0.1:9200]
Valet will now proxy [http://site2.test] traffic to [http://127.0.0.1:9200]
Valet will now proxy [http://site3.test] traffic to [http://127.0.0.1:9200]
```

###### proxy --secure

`--secure` option allows you to secure the proxy site. It is boolean, so if it's present it's `true`, otherwise `false`.

```console
$ valet proxy site1 https://127.0.0.1:9200 --secure
Valet will now proxy [https://site1.test] traffic to [https://127.0.0.1:9200]
```

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

##### proxies

```
proxies  List all the proxy sites
```

```console
$ valet proxies
┌───────┬─────────┬────────────────────┬────────────────────────┐
| Site  | Secured | URL                | Host                   |
├───────┼─────────┼────────────────────┼────────────────────────┤
| site1 | X       | https://site1.test | https://127.0.0.1:9200 |
└───────┴─────────┴────────────────────┴────────────────────────┘
```

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

##### unproxy

```
unproxy [site]  Remove a proxied site
```

Just like the `proxy` command, you may unproxy multiple sites at once by separating them with commas.

```console
$ valet unproxy site1
Valet will no longer proxy [http://site1.test].

$ valet unproxy site1,site2,site3
Valet will no longer proxy [http://site1.test].
Valet will no longer proxy [http://site2.test].
Valet will no longer proxy [http://site3.test].
```

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

##### sites

```
sites  List all the parked, linked, and proxied sites
```

```console
$ valet sites
┌──────────────┬────────────┬─── Parked ───────────────┬────────────────────────┐
| Site         | PHP        | URL                      | Path                   |
├──────────────┼────────────┼──────────────────────────┼────────────────────────┤
| site1        | 7.4.33     | https://site1.test       | D:\_Sites\site1        |
|              | (isolated) |                          |                        |
├──────────────┼────────────┼──────────────────────────┼────────────────────────┤
| site-test    | 8.1.8      | http://site-test.test    | D:\_Sites\site-test    |
|              | (default)  |                          |                        |
├──────────────┼────────────┼──────────────────────────┼────────────────────────┤
| awesome site | 8.1.8      | http://awesome site.test | D:\_Sites\awesome site |
|              | (default)  |                          |                        |
└──────────────┴────────────┴──────────────────────────┴────────────────────────┘

┌──────────────┬────────────┬───────── Linked ─────────┬────────────────────────┐
| Site         | PHP        | URL                      | Path                   |
├──────────────┼────────────┼──────────────────────────┼────────────────────────┤
| awesome_site | 8.1.8      | http://awesome_site.test | D:\_Sites\awesome site |
|              | (default)  |                          |                        |
└──────────────┴────────────┴──────────────────────────┴────────────────────────┘

┌─────────┬───────────────── Proxied──┬────────────────────────┐
| Site    | URL                       | Host                   |
├─────────┼───────────────────────────┼────────────────────────┤
| my_site | http://awesome_site.test  | http://127.0.0.1:9200  |
└─────────┴───────────────────────────┴────────────────────────┘
```

### Sharing

###### Note: ngrok ships with Valet internally, there is no need to download it separately.

##### share

```
share                Share the current working directory site with a publically accessible URL
      [site]         Optionally, specify a site
      [-o|--options] Optionally, specify ngrok options/flags
      [--debug]      Allow error messages to output to the current terminal
```

```console
$ cd /d/sites/site1
$ valet share

$ valet share site1
```

Share your local site publically. ngrok will do all the magic for you and give you a publically accessible URL to share to clients or team members.

Before sharing a site with ngrok, you must first set the authtoken using Valet's [`set-ngrok-token` command](#authset-ngrok-token).

When using the command, a new CMD terminal will be launched with the ngrok information, including the public URL to share.

###### Note: The URL won't be copied to the clipboard, however, in a separate terminal, you can use the [`fetch-share-url` command](#fetch-share-url).

###### share --options

`--options` (shortcut `-o`) [optional] is ngrok's options/flags for it's `http` command (which `valet share` uses internally). It is a string, but multiple options can be specified. Please see the [important notes](#notes-for-all---options) about this option.

```console
$ valet share site1 --options domain=example.com//region=eu//request-header-remove="header to remove"

$ cd /d/sites/site1
$ valet share -o domain=example.com
```

###### Note: If you're already sharing a project, and try to share another project simultaneously, the new cmd window may open for a split second and then close. This is due to ngrok failing silently, and won't output any error messages. To output the errors, pass the `--debug` flag to the command. This will cause ngrok to try to run in the current terminal instead of a new window, thus sending the error messages.

##### auth|set-ngrok-token

```
auth | set-ngrok-token [authtoken] Set the ngrok authtoken.
```

`auth` is a command alias.

```console
$ valet set-ngrok-token 123abc
$ valet auth 123abc
Authtoken saved to configuration file: C:/Users/Username/.config/valet/ngrok/ngrok.yml
```

Before sharing a site with ngrok, you must first set the authtoken, which can be accessed in your ngrok account.

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

##### url|fetch-share-url

```
url | fetch-share-url          Get and copy the public URL of the current working directory site that is currently being shared
                       [site]  Optionally, specify a site
```

`url` is a command alias.

Once sharing a site with `valet share`, you can get the public URL using this command in a separate terminal. The URL will be outputted to the terminal and will also be copied to the clipboard for ease of use.

```console
$ cd /d/sites/site1
$ valet fetch-share-url
The public URL for site1 is [ngrok public URL]
It has been copied to your clipboard.

$ valet fetch-share-url site1
$ valet url site1
The public URL for site1 is [ngrok public URL]
It has been copied to your clipboard.
```

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

##### ngrok

```
ngrok [commands] Run ngrok commands
      [-o|--options] Specify ngrok options/flags.
```

Because ngrok CLI has a multitude of commands and options, the `valet ngrok` command is very useful for passing through any and all commands to ngrok.

###### ngrok commands

The `commands` argument is a space-separated array of the commands plus it's argument's values.

```console
$ valet ngrok config add-authtoken 123abc
```

###### ngrok --options

The `--options` (shortcut `-o`) [optional] can be used to pass options/flags to ngrok. It is a string, but multiple options can be specified. Please see the [important notes](#notes-for-all---options) about this option.

```console
$ valet ngrok config add-authtoken 123abc --options config=C:/path/ngrok.yml//log=false
$ valet ngrok config add-authtoken 123abc -o config=C:/path/ngrok.yml//log=false
```

### Other commands

##### tld

```
tld         Get the TLD currently being used by Valet
     [tld]  Optionally, set a new TLD
```

`tld` gets the current Top Level Domain (TLD) that Valet is using.

```console
$ valet tld
test
```

When developing, you may like to use a different TLD. Valet serves sites on the default `.test` TLD, but you can also set a different one with this command, just pass the new TLD as it's argument.

```console
$ valet tld code
Your Valet TLD has been updated to [code].
```

It's important to note, when choosing a TLD, you need to be careful not to use one that could potentially be used on the Web. If your site name and TLD match a real web address, then the browser will resolve the request and redirect it to the real website instead of Valet resolving it to your local project.

Example: Your local Roots Bedrock site on `bedrock.dev` will redirect to the Minecraft Bedrock website.

Generally, the [special-use domains listed on Wikipedia](https://en.wikipedia.org/wiki/List_of_Internet_top-level_domains#Special-Use_Domains), and any unique custom ones like `.code`, are all good to use as they are invalid TLDs.

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.
Note: the Mac version has officially discontinued it's use; whereas Valet Windows 3 won't.

##### which

```
which  Determine which Valet driver the current working directory is using
```

```console
$ cd /d/sites/site1
$ valet which
This site is served by [BasicValetDriver].
```

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

##### paths

```
paths  List all of the paths registered with Valet
```

`paths` allows you to view all registered paths. The default path is `.config/valet/Sites`, any others are added via the [`park`](#park) command.

```console
$ valet paths
┌───────────────────────────────────────┐
│ Paths                                 │
├───────────────────────────────────────┤
│ C:/Users/Username/.config/valet/Sites │
├───────────────────────────────────────┤
│ D:/Sites                              │
└───────────────────────────────────────┘
```

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

##### open

```
open          Open the current working directory site in the browser
      [site]  Optionally, specify a site
```

```console
$ cd /d/sites/site1
$ valet open

$ valet open site1
```

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

##### latest|on-latest-version

```
latest | on-latest-version  Determine if this is the latest version/release of Valet
```

`latest` is a command alias.

```console
$ valet on-latest-version
$ valet latest
Yes
```

`on-latest-version` determines whether the installed version of Valet is the latest. If not, then Valet will prompt you to update.

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

##### log

```
log                 View and tail a log file
     [key]          The name of the log
     [-f|--follow]  Tail real-time streaming output of the changing file
     [-l|--lines]   The number of lines to view
```

```console
$ valet log nginx --follow --lines=3
$ valet log nginx -f -l 3
```

`log` allows you to view the logs which are written by Valet's services. To view a list of logs, simply run `valet log`.

```console
$ valet log
┌───────────────────────┬───────────────────────────────────────────────────────────────┐
│ Key                   │ File                                                          │
├───────────────────────┼───────────────────────────────────────────────────────────────┤
│ nginx                 │ C:/Users/Username/.config/valet/Log/nginx-error.log           │
├───────────────────────┼───────────────────────────────────────────────────────────────┤
│ nginxservice.err      │ C:/Users/Username/.config/valet/Log/nginxservice.err.log      │
├───────────────────────┼───────────────────────────────────────────────────────────────┤
│ nginxservice.out      │ C:/Users/Username/.config/valet/Log/nginxservice.out.log      │
├───────────────────────┼───────────────────────────────────────────────────────────────┤
│ nginxservice.wrapper  │ C:/Users/Username/.config/valet/Log/nginxservice.wrapper.log  │
├───────────────────────┼───────────────────────────────────────────────────────────────┤
│ phpcgiservice.err     │ C:/Users/Username/.config/valet/Log/phpcgiservice.err.log     │
├───────────────────────┼───────────────────────────────────────────────────────────────┤
│ phpcgiservice.out     │ C:/Users/Username/.config/valet/Log/phpcgiservice.out.log     │
├───────────────────────┼───────────────────────────────────────────────────────────────┤
│ phpcgiservice.wrapper │ C:/Users/Username/.config/valet/Log/phpcgiservice.wrapper.log │
└───────────────────────┴───────────────────────────────────────────────────────────────┘
```

The `key` is required in order to view a log. It can be found from the list of logs.

###### --follow

The `--follow` (shortcut `-f`) option can be used to tail real-time streaming output of the changing file.

```console
$ valet log nginx --follow
$ valet log nginx -f
```

###### --lines

The `--lines` (shortcut `-l`) option changes the number of lines to view from the log.

```console
$ valet log nginx --lines=3
$ valet log nginx -l 3
```

###### Note: This command uses the Unix-like Git Bash `tail` command, therefore this command will currently only work in Git Bash.

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

##### services

```
services  List the installed Valet services
```

```console
$ valet services
Checking the Valet services...
┌───────────────────┬────────────────────────────────┬───────────────┐
| Service           | Windows Name                   | Status        |
├───────────────────┼────────────────────────────────┼───────────────┤
| acrylic           | AcrylicDNSProxySvc             | running       |
├───────────────────┼────────────────────────────────┼───────────────┤
| nginx             | valet_nginx                    | running       |
├───────────────────┼────────────────────────────────┼───────────────┤
| php 8.1.8         | valet_php8.1.8cgi-9001         | running       |
├───────────────────┼────────────────────────────────┼───────────────┤
| php 7.4.33        | valet_php7.4.33cgi-9002        | running       |
├───────────────────┼────────────────────────────────┼───────────────┤
| php-xdebug 8.1.8  | valet_php8.1.8cgi_xdebug-9101  | not installed |
├───────────────────┼────────────────────────────────┼───────────────┤
| php-xdebug 7.4.33 | valet_php7.4.33cgi_xdebug-9102 | running       |
└───────────────────┴────────────────────────────────┴───────────────┘
```

##### directory-listing

```
directory-listing            Determine directory-listing behaviour. Default is off, which means a 404 will display
                   [status]  Optionally, switch directory listing [on, off]
```

```console
$ valet directory-listing
Directory listing is off

$ valet directory-listing on
Directory listing setting is now: on
```

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

##### diagnose

```
diagnose                Output diagnostics to aid in debugging Valet.
          [-p|--print]  Optionally print diagnostics output while running
          [--plain]     Optionally print and format output as plain text (aka, pretty print)
```

```console
$ valet diagnose
Running diagnostics...
[Diagnostics output here]
```

The diagnostics will be copied to the clipboard as formatted HTML for easy issue reporting.

<img align="center" src="./The_same_icon.svg" style="width:20px;"> This command is the same as the Mac version.

### Notes for all `--options`

These are **important notes** for the commands that have the `--options` or `--valetOptions`.

- The `--options`, `--valetOptions` (shortcut `-o`) options can be used to pass options/flags to the service related to that command.

  Just pass the option name without the `--` prefix eg. `--options=config=C:/path/ngrok.yml` (example for the `ngrok` command). This is so that Valet doesn't get confused with it's own options.

  All options/flags that are passed will be prefixed with `--` after Valet has processed the command, unless it's a shortcut of a single character, then it will be prefixed with `-`. The example above will run as `--config=C:/path/ngrok.yml`.

- The `=` immediately after the command option is optional, if it's omitted, you must use a space instead.

  ```
  --options=option1
  --options option1
  --valetOptions=option1
  --valetOptions option1
  ```

- The options also have `-o` shortcuts and it cannot have the `=` character, it must use a space for separation.

  ```console
  -o option1
  ```

  This falls inline with Symfony's docs and complies with command-line standards.

  ###### From [Symfony's docs](https://symfony.com/doc/current/console/input.html#using-command-options):

  > ###### Note that to comply with the docopt standard, long options can specify their values after a whitespace or an `=` sign (e.g. `--iterations 5` or `--iterations=5`), but short options can only use whitespaces or no separation at all (e.g. `-i 5` or `-i5`).

- The options also allows multiple options to be passed, they just need to be separated with double slashes `//`.

  ```console
  --valetOptions=option1//option2//option3
  --options option1//option2//option3
  -o option1//option2//option3
  ```

### Command Parity Checker

Commands that have been tested and made parity:

- [ ] composer
- [x] diagnose
- [x] directory-listing
- [x] fetch-share-url
- [x] forget
- [x] install
- [x] isolate
- [x] isolated
- [x] link
- [x] links
- [x] log
- [ ] loopback
- [x] on-latest-version
- [x] open
- [x] park
- [x] parked
- [x] paths
- [ ] php
- [x] proxies
- [x] proxy
- [x] restart
- [x] secure
- [x] secured
- [x] set-ngrok-token
- [x] share
- [ ] share-tool
- [x] start
- [x] status - renamed to services
- [x] stop
- [x] tld
- [ ] trust
- [x] uninstall
- [x] unisolate
- [x] unlink
- [x] unproxy
- [x] unsecure
- [x] use
- [x] which
- [x] which-php - renamed to php:which

### Commands not supported

`valet loopback` - N/A

`valet trust` - N/A

`valet status` - In favour of the `valet services` command

`valet php` (proxying commands to PHP CLI) - Possible far future feature?

`valet composer` (proxying commands to Composer CLI) - N/A

`valet which-php` - In favour of the `valet php:which` command

`valet share-tool` - Upcoming feature

For commands that are referenced as "the same as the Mac version", please refer to the official documentation on the [Laravel website](https://laravel.com/docs/8.x/valet#serving-sites) for more information.

## Known Issues

- WSL2 distros fail because of Acrylic DNS Proxy ([microsoft/wsl#4929](https://github.com/microsoft/WSL/issues/4929)). Use `valet stop`, start WSL2 then `valet start`.
- The PHP-CGI process uses port 9001. If it's already used change it in `~/.config/valet/config.json` and run `valet install` again.
- When sharing sites the url will not be copied to the clipboard.
- ~~You must run the `valet` commands from the drive where Valet is installed, except for park and link. See [#12](https://github.com/cretueusebiu/valet-windows/issues/12#issuecomment-283111834).~~ All commands seem to work fine on all drives.
- If your machine is not connected to the internet you'll have to manually add the domains in your `hosts` file or you can install the [Microsoft Loopback Adapter](https://docs.microsoft.com/en-us/troubleshoot/windows-server/networking/install-microsoft-loopback-adapter) as this simulates an active local network interface that Valet can bind too.
- When trying to run Valet on PHP 7.4 and you get this error:

  > Composer detected issues in your platform:
  >
  > Your Composer dependencies require a PHP version ">= 8.1.0". You are running 7.4.33.
  >
  > PHP Fatal error: Composer detected issues in your platform: Your Composer dependencies require a PHP version ">= 8.1.0". You are running 7.4.33. in C:\Users\Username\AppData\Roaming\Composer\vendor\composer\platform_check.php on line 24

  It means that a dependency of Valet's dependencies requires 8.1. You can rectify this error by running `composer global update` while on 7.4, and composer will downgrade any global dependencies to versions that will work on 7.4. See this [Stack Overflow answer](https://stackoverflow.com/a/75080139/2358222).

  ###### NOTE #1: This will of course downgrade all global packages. Depending on the packages, it may break some things. If you just want to downgrade valet dependencies, then you can specify the Valet namespace. `composer global update ycodetech/valet-windows`.

  ###### NOTE #2: It's recommended to use PHP 8.1 anyway, downgrading will mean some things may break or cause visual glitches in the terminal output. So downgrade at your own risk.

  ###### Note #3: Make sure you uninstall Valet before `composer global update`, to make sure all services have been stopped and uninstalled before composer removes and updates them.

- If you're using a framework that uses a .env file and sets the domain name, such as `WP_HOME` for Laravel Bedrock, then make sure the TLD is the same as the one set for Valet. Otherwise, when trying to reach a site, the site will auto redirect to use the TLD in set in the .env.

  Example: `WP_HOME='http://mySite.test'`, Valet gets a request to `http://mySite.dev`, the site will auto redirect to `http://mySite.test`.

  If this still happens after changing the TLD, then it has been cached by the browser, despite NGINX specifying headers not to cache. To rectify try `"Empty cache and hard reload"` option of the page reload button.

## Xdebug Installation

Valet only installs a specific Xdebug PHP CGI service on a separate port to work alongside the PHP service. To install Xdebug itself, follow the [official guide](https://xdebug.org/docs/install).

When configuring Xdebug, make sure to set the Xdebug port to the same port that Valet has setup for the Xdebug CGI service. You can find this written in the Valet config, or use the [php:list](#phplist) command.

To enable a debugging session you can use [Xdebug helper](https://chrome.google.com/webstore/detail/xdebug-helper/eadndfjplgieldjbigjakmdgkmoaaaoc), or set a cookie with the name `XDEBUG_SESSION`, or a VScode extension.

###### Dev comment: I'm not entirely sure how Xdebug works. So please refer to online guides for further guidance.

## Contributions

Taylor Otwell, et al - [Laravel Valet](https://github.com/laravel/valet)

Cretueusebiu - [Valet Windows port](https://github.com/cretueusebiu/valet-windows)

Iamroi - [Valet Windows: Feature PR for multiple PHP support](https://github.com/cretueusebiu/valet-windows/pull/195)

Damsfx - [Valet Windows: Patch 1 PR on Iamroi's repo to add PHP versions to `links` command](https://github.com/iamroi/valet-windows/pull/1)

yCodeTech - [Laravel Valet Windows 3](https://github.com/yCodeTech/valet-windows)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## License

Laravel Valet is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
