FROM mcr.microsoft.com/windows:1903

USER ContainerAdministrator

WORKDIR c:\\valet

RUN powershell -Command Set-ExecutionPolicy RemoteSigned
RUN powershell -Command [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.SecurityProtocolType]::Tls12

# Install cURL
COPY ./tests/docker/curl.ps1 ./curl.ps1
RUN powershell -Command .\curl.ps1; rm curl.ps1

# Install VC Redist
RUN powershell -Command curl.exe https://download.visualstudio.microsoft.com/download/pr/89a3b9df-4a09-492e-8474-8f92c115c51d/B1A32C71A6B7D5978904FB223763263EA5A7EB23B2C44A0D60E90D234AD99178/VC_redist.x64.exe -o vc_redist.x64.exe; Start-Process -FilePath vc_redist.x64.exe -ArgumentList "/passive" -Wait -Passthru; rm vc_redist.x64.exe

# Install PHP
COPY ./bin/php.ps1 ./php.ps1
RUN powershell -Command .\php.ps1 7.4; rm php.ps1

# Install Composer
COPY ./tests/docker/composer.bat /bin/composer.bat
# COPY ./tests/docker/composer.json /Users/ContainerAdministrator/AppData/Roaming/Composer/composer.json
RUN powershell -Command curl.exe https://getcomposer.org/download/2.0.8/composer.phar -o /bin/composer.phar; $env:Path += ';C:\bin;C:\valet'; [Environment]::SetEnvironmentVariable('Path', $env:Path, [EnvironmentVariableTarget]::Machine)

# Copy Valet
COPY ./ ./

# Install Valet
RUN powershell -Command valet install

CMD [ "powershell" ]

# docker build -t cretueusebiu/valet-windows -f ./tests/docker/Dockerfile .
# docker run -it --rm --name cretueusebiu-valet-windows -v "C:\Users\Eusebiu\Code\valet-windows:C:\valet" cretueusebiu/valet-windows
