<p align="center"><img src="./laravel_valet_windows_3_logo.svg" style="width:500px; background: none;"></p>

<p align="center">
<a href="https://packagist.org/packages/ycodetech/valet-windows"><img src="https://poser.pugx.org/ycodetech/valet-windows/downloads" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/ycodetech/valet-windows"><img src="https://poser.pugx.org/ycodetech/valet-windows/v" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/ycodetech/valet-windows"><img src="https://poser.pugx.org/ycodetech/valet-windows/license" alt="License"></a>
</p>

<p align="center">This is a Windows port of the popular Mac development environment <a href="https://github.com/laravel/valet">Laravel Valet</a>.</p>
<p align="center">Laravel Valet <i>Windows</i> 3 is a much needed updated fork of <a href="https://github.com/cretueusebiu/valet-windows">cretueusebiu/valet-windows</a>, with lots of improvements and new commands. This version hopes to achieve as much parity as possible with the Mac version.</p>

## !! This is in active development, official release coming soon !!

### !! The dev release can now be installed via `composer global require ycodetech/valet-windows` !!

<p align="center"><img src="./composer_laravel_valet_windows_3_logo.svg" style="width:400px; background: none;"></p>

> **Warning** **If you're coming from <a href="https://github.com/cretueusebiu/valet-windows">cretueusebiu/valet-windows</a>, then you need to make sure to fully uninstall it from your computer, deleting all configs, and removing from composer with `composer global remove cretueusebiu/valet-windows`, before installing this 3.0 version.**

<br>

[Introduction](#introduction) | [Documentation](#documentation) | [Commands](#commands) | [Commands Not Supported](#commands-not-supported) | [Known Issues](#known-issues) | [Xdebug](#xdebug) | [Testing](#testing) | [Contributions](#contributions)

---

<table>
  <tr>
    <th>Commands Section</th>
    <td ><a href="#installinguninstalling--startingstopping">Installing/Uninstalling & Starting/Stopping</a></td>
    <td ><a href="#php-services">PHP Services</a></td>
    <td align="center"><a href="#using-php-versions">Multi-PHP Versions and Securing</a></td>
    <td align="center"><a href="#parked-and-linked">Parked, Linked, Proxies and Sites</a></td>
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
    <td><a href="#phplist">php:list</a></td>
    <td align="center"><a href="#isolated">isolated</a></td>
    <td align="center"><a href="#unparkforget">unpark|forget</a></td>
    <td align="center"><a href="#urlfetch-share-url">url|fetch-share-url</a></td>
    <td align="center"><a href="#paths">paths</a></td>

  </tr>
  <tr>
  <th></th>
    <td><a href="#restart">restart</a></td>
    <td><a href="#phpwhich">php:which</a></td>
    <td align="center"><a href="#unisolate">unisolate</a></td>
    <td align="center"><a href="#link">link</a></td>
    <td align="center"><a href="#ngrok">ngrok</a></td>
		<td align="center"><a href="#open">open</a></td>

  </tr>
  <tr>
  <th></th>
    <td><a href="#stop">stop</a></td>
    <td><a href="#phpinstall">php:install</a></td>
		<td align="center"><a href="#secure">secure</a></td>
		<td align="center"><a href="#links">links</a></td>
    <td></td>
		<td align="center"><a href="#lateston-latest-version">latest|on-latest-version</a></td>

  </tr>

  <tr>
  <th></th>
    <td><a href="#uninstall">uninstall</a></td>
    <td><a href="#phpuninstall">php:uninstall</a></td>
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

---

- If you don't have PHP installed, open PowerShell (3.0+) as Administrator and run:

  ```powershell
  # PHP 8.1
  Set-ExecutionPolicy RemoteSigned -Scope Process; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri "https://github.com/ycodetech/valet-windows/raw/master/bin/php.ps1" -OutFile $env:temp\php.ps1; .$env:temp\php.ps1 "8.1"

  # PHP 8.0
  Set-ExecutionPolicy RemoteSigned -Scope Process; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri "https://github.com/ycodetech/valet-windows/raw/master/bin/php.ps1" -OutFile $env:temp\php.ps1; .$env:temp\php.ps1 "8.0"

  # PHP 7.4
  Set-ExecutionPolicy RemoteSigned -Scope Process; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri "https://github.com/ycodetech/valet-windows/raw/master/bin/php.ps1" -OutFile $env:temp\php.ps1; .$env:temp\php.ps1 "7.4"
  ```

  This script will download and install PHP for you and add it to your environment path variable. PowerShell is only required for this step.

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
        [--xdebug] Optionally, install Xdebug for PHP.
```

This installs all Valet services:

- Nginx
- PHP CGI
- PHP Xdebug CGI [optional]
- Acrylic DNS
- Ansicon

And it's configs in `C:\Users\Username\.config\valet`.

Once complete, Valet will automatically start the services.

```console
$ valet install
Valet installed and started successfully!
```

###### Note: If `install` is ran again when it's already installed, Valet will ask if you want to proceed to reinstall.

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

`valetCommand` is the Valet command, plus it's arguments values that you wish to run. It is a string array separated by spaces.

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
       [service] Optionally, pass a specific service name to start
```

```console
$ valet start
Valet services have been started.

$ valet start nginx
Nginx has been started.
```

##### restart

```
restart            Restarts Valet's services
         [service] Optionally, pass a specific service name to restart
```

```console
$ valet restart
Valet services have been restarted.

$ valet start nginx
Nginx has been started.
```

##### stop

```
stop            Stops Valet's services
      [service] Optionally, pass a specific service name to stop
```

```console
$ valet stop
Valet services have been stopped.

$ valet start nginx
Nginx has been stopped.
```

##### uninstall

```
uninstall                   Uninstalls Valet's services
           [--force]        Force uninstallation without a confirmation question
		   [-p|--purge-config] Purge and remove all Valet configs
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
php:add    [path]   Add PHP by specifying a path
```

```console
$ valet php:add "C:\php\7.4"
```

###### **Note:** When adding PHP, the full version number (eg. 7.4.33) will be extracted and an alias (eg. 7.4) will be generated. Either of these can be used in other commands.

###### Furthermore, the details of the versions will be written to the config in a natural decending order that adheres to decimals. This means that when two minor versions (like 8.1.8 and 8.1.18) of an alias (8.1) are added, and the default PHP is then set to use the alias, then Valet will use the most recent version of the alias, which in this case would be 8.1.18.

##### php:remove

```
php:remove [path]   Remove PHP by specifying a path
```

```console
$ valet php:remove "C:\php\7.4"
```

##### php:list

```
php:list            List all PHP versions and services
```

```console
$ valet php:list
Listing PHP services...
+---------+---------------+------------+------+-------------+---------+
| Version | Version Alias | Path       | Port | xDebug Port | Default |
+---------+---------------+------------+------+-------------+---------+
| 8.1.8   | 8.1           | C:\php\8.1 | 9006 | 9106        | X       |
| 7.4.33  | 7.4           | C:\php\7.4 | 9004 | 9104        |         |
+---------+---------------+------------+------+-------------+---------+
```

##### php:which

```
php:which  [site]   To determine which PHP version the current working directory or a specified site is using
```

```console
$ valet php:which
The current working directory site1 is using PHP 7.4.33 (isolated)

$ valet php:which site2
The specified site site2 is using PHP 8.1.8 (default)
```

##### php:install

```
php:install          Reinstall all PHP services from [valet php:list]
```

```console
$ valet php:install
```

##### php:uninstall

```
php:uninstall        Uninstall all PHP services from [valet php:list]
```

```console
$ valet php:uninstall
```

### Using PHP versions

##### use

```
use       [phpVersion]  Set or change the default PHP version used by Valet. Either specify the full version or the alias
```

```console
$ valet use 8.1
Setting the default PHP version to [8.1].
Valet is now using 8.1.18.

$ valet use 8.1.8
Setting the default PHP version to [8.1.8].
Valet is now using 8.1.8.
```

##### isolate

```
isolate   [phpVersion]  Isolates the current working directory to a specific PHP version
          [--site=]     Optionally specify the site instead of the current working directory
```

###### Note: You can isolate 1 or more sites at a time. Just pass the `--site` option for each of the sites you wish to isolate to the same PHP version.

```console
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

##### unisolate

```
unisolate [--site=]     Removes [unisolates] an isolated site
          [--all]       Optionally removes all isolated sites
```

```console
$ valet unisolate
Unisolating the current working directory...
The site [my_site] is now using the default PHP version.

$ valet unisolate --site=my_site
The site [my_site] is now using the default PHP version.

$ valet unisolate --all
The site [my_site] is now using the default PHP version.
The site [site1] is now using the default PHP version.
```

##### isolated

```
isolated                List isolated sites
```

```console
$ valet isolated
+----------+--------+
| Site     | PHP    |
+----------+--------+
| site1    | 7.4.33 |
| my_site  | 7.4.33 |
+----------+--------+
```

### Parked and Linked

##### link

```
link   [name]        Register the current working directory as a symbolic link with a different name
       [--secure]    Optionally secure the site
       [--isolate=]  Optionally isolate the site to a specified PHP version
```

```console
$ valet link my_site_renamed
A [my_site_renamed] symbolic link has been created in [C:/Users/Username/.config/valet/Sites/my_site_renamed].

$ valet link cool_site --secure
A [cool_site] symbolic link has been created in [C:/Users/Username/.config/valet/Sites/cool_site].
The [cool_site.test] site has been secured with a fresh TLS certificate.

$ valet link cool_Site --isolate=7.4
A [cool_site] symbolic link has been created in [C:/Users/Username/.config/valet/Sites/cool_site].
The site [cool_site.test] is now using 7.4.

$ valet link cool_Site --secure --isolate=7.4
A [cool_site] symbolic link has been created in [C:/Users/Username/.config/valet/Sites/cool_site].
The [cool_site.test] site has been secured with a fresh TLS certificate.
The site [cool_site.test] is now using 7.4.
```

##### unlink

```
unlink [name]        Unlink a site
```

```console
$ valet unlink cool_site
Unsecuring cool_site...
The [cool_site] symbolic link has been removed.
```

##### links

```
links                Display all registered symbolic links
```

```console
$ valet links
+-----------------+-----+------------------+-----------------------------+---------------------------------------+
| Site            | SSL | PHP              | URL                         | Path                                  |
+-----------------+-----+------------------+-----------------------------+---------------------------------------+
| my_site_renamed |     | 8.1.18 (default) | http://my_site_renamed.code | D:\_Sites\a_completely_different_name |
| cool_site       | X   | 7.4.33 (isolated)| http://cool_site.code       | D:\_Sites\cool and awesome site       |
+-----------------+-----+------------------+-----------------------------+---------------------------------------+
```

##### parked

```
parked               Display all current sites within parked paths
```

###### Note: If there's a parked site that is also a symbolic linked site, then it will also output the linked site name (aka alias) and it's URL (aka alias URL).

```console
$ valet parked
+-----------------------------------------------+
|      Site: site1                              |
|     Alias:                                    |
|       SSL:                                    |
|       PHP: 7.4.33 (isolated)                  |
|       URL: http://site1.test                  |
| Alias URL:                                    |
|      Path: D:\_Sites\site1                    |
|-----------------------------------------------|
|      Site: another site                       |
|     Alias: another_site_renamed               |
|       SSL:                                    |
|       PHP: 8.1.18 (default)                   |
|       URL: http://another site.test           |
| Alias URL: http://another_site_renamed.test   |
|      Path: D:\_Sites\another site             |
+-----------------------------------------------+
```

### Sharing

###### Note: ngrok ships with Valet internally, there is no need to download it separately.

##### share

```
share [site]         Optionally, specify a site. Otherwise the default is the current working directory.
      [-o|--options] Specify ngrok options/flags.
      [--debug]      Output error messages to the current terminal.
```

```console
$ valet share site1

/d/sites/site1
$ valet share
```

Share your local site publically. ngrok will do all the magic for you and give you a publically accessible URL to share to clients or team members.

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

##### set-ngrok-token

```
auth | set-ngrok-token [authtoken] Set the ngrok authtoken.
```

<small>`auth` is a command alias.</small>

```console
$ valet set-ngrok-token 123abc
$ valet auth 123abc
Authtoken saved to configuration file: C:/Users/Username/.config/valet/ngrok/ngrok.yml
```

Before sharing a site with ngrok, you must first set the authtoken, which can be accessed in your ngrok account.

##### fetch-share-url

```
url | fetch-share-url [site] Get the public URL of the site that is currently being shared.
```

<small>`url` is a command alias.</small>

```console
$ valet fetch-share-url site1
$ valet url site1
The public URL for site1 is [ngrok public URL]
It has been copied to your clipboard.
```

Once sharing a site with `valet share`, you can get the public URL using this command in a separate terminal. The URL will be outputted to the terminal and will also be copied to the clipboard for ease of use.

##### ngrok

```
ngrok [commands] Run ngrok commands, arguments, and values.
      [-o|--options] Specify ngrok options/flags.
```

```
$ valet ngrok config add-authtoken 123abc --options=config=C:/path/ngrok.yml
```

Because ngrok CLI has a multitude of commands and options, the `valet ngrok` command is very useful for passing through any and all commands to ngrok.

###### ngrok commands

The `commands` argument is a space-separated array of the commands, arguments, and values.

###### ngrok --options

The `--options` (shortcut `-o`) [optional] can be used to pass options/flags to ngrok. It is a string, but multiple options can be specified. Please see the [important notes](#notes-for-all---options) about this option.

```console
$ valet ngrok config add-authtoken 123abc --options config=C:/path/ngrok.yml//log=false
$ valet ngrok config add-authtoken 123abc -o config=C:/path/ngrok.yml//log=false
```

### Other commands

##### services

```
services    List the installed Valet services
```

```console
$ valet services
Checking the Valet services...
+-------------------+--------------------------------+---------+
| Service           | Windows Name                   | Status  |
+-------------------+--------------------------------+---------+
| acrylic           | AcrylicDNSProxySvc             | running |
| nginx             | valet_nginx                    | running |
| php 8.1.8         | valet_php8.1.8cgi-9006         | running |
| php 7.4.33        | valet_php7.4.33cgi-9004        | running |
| php-xdebug 8.1.8  | valet_php8.1.8cgi_xdebug-9106  | running |
| php-xdebug 7.4.33 | valet_php7.4.33cgi_xdebug-9104 | running |
+-------------------+--------------------------------+---------+
```

##### secure

```
secure [domain]   Secure the specified domain with a trusted TLS certificate.
```

```console
$ valet secure site1
The [site1.test] site has been secured with a fresh TLS certificate.
```

###### Note: If you use VS Code integrated terminal, the secure command (or secure option in on other commands) won't work and will need to be ran in a standalone terminal with admin privileges.

##### on-latest-version

```
latest | on-latest-version
```

<small>`latest` is a command alias.</small>

```console
$ valet on-latest-version
$ valet latest
Yes
```

Determine whether the installed version of Valet is the latest.

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

### Commands not supported

`valet loopback`

`valet trust`

`valet status` - In favour of the `valet services` command

`valet php` (proxying commands to PHP CLI)

`valet composer` (proxying commands to Composer CLI)

`valet which-php` - In favour of the `valet php:which` command

`valet share-tool`

For other commands that have not changed, please refer to the official documentation on the [Laravel website](https://laravel.com/docs/8.x/valet#serving-sites).

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
