$phpVersion = $args[0]
$phpPath = "C:\php"
$caCertUrl = "https://curl.haxx.se/ca/cacert.pem"
$phpReleasesUrl = "http://windows.php.net/downloads/releases"
$stubsUrl = "https://raw.githubusercontent.com/cretueusebiu/valet-windows/master/cli/stubs"

if (Get-Command "php" -errorAction SilentlyContinue) {
    Write-Output "PHP already installed!"
    exit
}

if ('7.2','7.3','7.4','8.0' -notcontains $phpVersion) {
    throw "Invalid PHP version [$phpVersion]."
}

function Find-PhpRelease {
    $phpResponse = Invoke-WebRequest "$phpReleasesUrl/releases.json" -UseBasicParsing | ConvertFrom-Json
    $path = $phpResponse.$phpVersion."nts-VC15-x64"."zip"."path"

    if (! $path) {
        throw "Could not find a release for PHP $phpVersion."
    }

    return "$phpReleasesUrl/$path"
}

function Download-File {
    Param ($Source, $Destination)

    $wc = New-Object System.Net.WebClient
    $wc.Headers.Add("User-Agent", "Windows Powershell WebClient")
    $wc.DownloadFile($Source, $Destination)
}

Add-Type -AssemblyName System.IO.Compression.FileSystem

$phpZip = "$PSScriptRoot\php.zip"

# Download php
Write-Output "Downloading PHP..."
$phpUrl = Find-PhpRelease
Download-File $phpUrl -Destination $phpZip

# Extract php
Write-Output "Installing PHP..."
[System.IO.Compression.ZipFile]::ExtractToDirectory($phpZip, $phpPath)
Remove-Item $phpZip

# Download php.ini
Write-Output "Installing php.ini..."
$phpIniUrl = $stubsUrl + "/php" + $phpVersion.ToString().Replace('.', '') + ".ini"
Download-File -Source $phpIniUrl -Destination "$phpPath\php.ini"

# Download cacert.pem
Write-Output "Installing CA certificate..."
Download-File -Source $caCertUrl -Destination "$phpPath\cacert.pem"

# Add PHP to path environment variable
Write-Output "Adding PHP to path..."
$env:Path += ";" + $phpPath
[Environment]::SetEnvironmentVariable("Path", $env:Path, [EnvironmentVariableTarget]::Machine)

# Done
Write-Output "PHP installed successfully!`n"
php -v
