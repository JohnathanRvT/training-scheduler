@echo off
SETLOCAL ENABLEEXTENSIONS ENABLEDELAYEDEXPANSION

SET /P PluginSlug="Enter your new plugin slug (e.g. my-awesome-plugin): "
SET /P PluginName="Enter your new plugin name (e.g. My Awesome Plugin): "

REM Rename main plugin file
IF EXIST plugin-name.php REN plugin-name.php %PluginSlug%.php
IF EXIST languages\plugin-name.pot REN languages\plugin-name.pot %PluginSlug%.pot

REM Replace inside files
FOR /R %%f IN (*.*) DO (
    powershell -Command "(Get-Content -Raw '%%f') -replace 'plugin-name', '%PluginSlug%' | Set-Content '%%f'"
    powershell -Command "(Get-Content -Raw '%%f') -replace 'JRVT Plugin Boilerplate', '%PluginName%' | Set-Content '%%f'"
)

echo Done. Plugin slug set to %PluginSlug%, name set to "%PluginName%".
pause
