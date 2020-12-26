$curlUrl = "https://curl.se/windows/dl-7.74.0_2/curl-7.74.0_2-win64-mingw.zip"
$curlZip = "C:\curl.zip"
$curlPath = "C:\curl"

function Download-File {
    Param ($Source, $Destination)

    $wc = New-Object System.Net.WebClient
    $wc.Headers.Add("User-Agent", "Windows Powershell WebClient")
    $wc.DownloadFile($Source, $Destination)
}

# Download cURL
Write-Output "Downloading cURL..."
Download-File $curlUrl -Destination $curlZip

# Install cURL
Write-Output "Installing cURL..."
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::ExtractToDirectory($curlZip, $curlPath)
Remove-Item $curlZip

# Add cURL to path environment variable
Write-Output "Adding cURL to path..."
$env:Path += ";" + $curlPath
[Environment]::SetEnvironmentVariable("Path", $env:Path, [EnvironmentVariableTarget]::Machine)
