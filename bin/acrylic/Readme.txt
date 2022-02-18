========================================================================================================================
ACRYLIC DNS PROXY
========================================================================================================================

Acrylic is a local DNS proxy for Windows which improves the performance of your computer by actively caching the
responses coming from your DNS servers and helps you fight unwanted ads through the use of a custom HOSTS file
(optimized for handling hundreds of thousands of domain names) with support for wildcards and regular expressions.

When you browse a web page a portion of the loading time is dedicated to name resolution (usually from a few
milliseconds to 1 second or more) while the rest is dedicated to the transfer of the web page contents and resources to
your browser. What Acrylic does is to reduce the time dedicated to name resolution for frequently visited addresses
closest to zero possible. It may not seem such a great optimization but in a few weeks of Internet browsing you will
probably save an hour or so, which is definitely not such a bad thing. Furthermore Acrylic's sliding expiration caching
mechanism and DNS silent updates are able to improve the browsing experience independently of the browser.

With Acrylic you can also gracefully overcome downtimes of your DNS servers without disrupting your work, because in
that case you will at least be able to connect to your favourite websites and to your email server.

Another good thing is that Acrylic is released as open source, which means that it's free and its source code, written
with Borland Delphi 7, is freely available to anyone under the GNU General Public License.

For more informations please use the "Acrylic Home Page" shortcut available from the "Start Menu", or go directly to:

https://mayakron.altervista.org/support/acrylic/Home.htm

Installed version is:
2.1.0 released on June 30, 2021.

========================================================================================================================
ACRYLIC DNS PROXY UI
========================================================================================================================

The Acrylic DNS Proxy UI desktop application understands the following command line options:

  InstallAcrylicService
    Performs all the necessary operations to install Acrylic as a Windows service on your computer.
  UninstallAcrylicService
    Performs all the necessary operations to uninstall Acrylic as a Windows service from your computer.
  StartAcrylicService
    Starts the Acrylic Windows service.
  StopAcrylicService
    Stops the Acrylic Windows service.
  RestartAcrylicService
    Restarts the Acrylic Windows service.
  PurgeAcrylicCacheData
    Purges Acrylic cache data, restarting the Acrylic Windows service if needed.
  ActivateAcrylicDebugLog
    Activates the Acrylic debug log, restarting the Acrylic Windows service if needed.
  DeactivateAcrylicDebugLog
    Deactivates the Acrylic debug log, restarting the Acrylic Windows service if needed.
  OpenAcrylicConfigurationFile
    Opens the Acrylic configuration file right after the application's startup.
  OpenAcrylicHostsFile
    Opens the Acrylic HOSTS file right after the application's startup.

Note: The Acrylic DNS Proxy UI desktop application icon has been designed by http://www.aha-soft.com/

========================================================================================================================
ACRYLIC DNS PROXY CONSOLE
========================================================================================================================

The console version (AcrylicConsole.exe) and the Windows service version of Acrylic (AcrylicService.exe) are
functionally identical, as they share the same code.

You may want to use the console version if you don't want to install anything on your computer (in this case I suppose
you chose the portable version of Acrylic, didn't you?) or if you are experimenting with Acrylic and you don't want to
restart the Windows service every time you change something in the configuration.

You cannot have both versions running at the same time because they will try to listen from the same UDP or TCP ports
and this can't happen. In this case you might see an error message similar to the following one written into the
AcrylicDebug.txt file (in the case of the Windows service version) or written to the standard output (in the case of the
console version):

Binding to IPv4 address 0.0.0.0 and port 53 failed with Windows Sockets error code 10048.

The console version of Acrylic currently understands the following command line options:

  /NoBanner
    Does not write the application banner to the console on startup.
  /NoLog
    Does not write the application log to the console while running.

========================================================================================================================