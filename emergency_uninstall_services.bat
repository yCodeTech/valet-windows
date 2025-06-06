@echo off

:: // TODO: Add a script to remove ansicon from the registry AutoRun value of HKEY_CURRENT_USER\Software\Microsoft\Command Processor. But it must not disturb other AutoRun values.

:: Check for admin rights and relaunch as admin if needed
net session >nul 2>&1
if %errorlevel% neq 0 (
	echo This script requires administrator privileges. Relaunching...
	powershell -Command "Start-Process '%~f0' -Verb RunAs"
	exit /b
)

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

@REM Delete all files in %UserProfile%\.config\valet\Services
del /q "%UserProfile%\.config\valet\Services\*.*"

echo "All services stopped and uninstalled."

pause
