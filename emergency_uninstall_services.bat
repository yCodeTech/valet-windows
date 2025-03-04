@echo off

echo "Stopping and uninstalling all services..."

@REM Stop and uninstall Acrylic process
echo Stopping AcrylicDNSProxySvc process...
taskkill /fi "Services eq AcrylicDNSProxySvc" /F
echo Uninstalling AcrylicDNSProxySvc process...
sc delete AcrylicDNSProxySvc

@REM Stop nginx process
echo Stopping nginx process...
taskkill /IM nginx.exe /F

for /f %%f in ('dir /b /s "%UserProfile%\.config\valet\Services\*.exe"') do (
	@REM %%~dpnf is the path to the executable without the extension
	echo Stopping %%~dpnf...
	%%~dpnf stop

	echo Uninstalling %%~dpnf...
	%%~dpnf uninstall
)

echo "All services stopped and uninstalled."
