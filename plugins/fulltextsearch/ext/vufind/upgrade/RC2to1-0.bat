@echo off

rem Make sure that environment edits are local and that we have access to the 
rem Windows command extensions.
setlocal enableextensions
if not errorlevel 1 goto extensionsokay
echo Unable to enable Windows command extensions.
goto end
:extensionsokay

rem Make sure the user is really ready to run this process
echo VuFind RC2 to 1.0 Upgrade Script
echo.
echo Before you run this script, make sure you have done these things:
echo.
echo 1) Take VuFind offline to prevent new data being created during
echo    the upgrade process.
echo 2) Move your RC2 directory to a new location, and unpack 1.0 into
echo    the old RC2 location.  DO NOT UNPACK 1.0 ON TOP OF RC2.  It is
echo    very important that you maintain separate directories.  Your RC2
echo    directory will not be modified by this process, so you can revert
echo    fairly easily if you need to by simply moving directories around.
echo 3) Back up your MySQL database.  This script makes only minor, harmless
echo    changes to the database, but you should make your own backup just in 
echo    case something goes wrong.
echo.
set /p GETSTARTED=Are you ready to begin? [y/N] 
echo.

if "%GETSTARTED%"=="Y" goto startupgrade
if "%GETSTARTED%"=="y" goto startupgrade
echo Upgrade aborted.
goto end
:startupgrade

rem cd to VuFind root dir as working directory 
rem all further operation depend on that
rem there is a hard coded assumption here that the upgrade script
rem is living in a subdir of the VuFind root dir!!!
cd /d %0\..\..
set VUFIND_PATH=%CD%

rem first adjust some paths
set /p YN=VuFind 1.0 is installed in %VUFIND_PATH%, correct? [Y/n] 
if "%YN%"=="Y" goto havenewpath
if "%YN%"=="y" goto havenewpath
if "%YN%"=="" goto havenewpath
:getnewpath
set /p VUFIND_PATH=Please enter the correct path: 
:havenewpath

rem check if there is a vufind.sh in VUFIND_PATH, if not ask for direction
if exist %VUFIND_PATH%\vufind.sh goto newpathchecked
echo There is no VuFind installation in %VUFIND_PATH%
goto getnewpath
:newpathchecked

cd /d %VUFIND_PATH%

echo Using %VUFIND_PATH% as installation path
echo.
echo Where is your old VuFind 1.0 RC2 installed?
:getoldpath
set /p OLD_VUFIND_PATH=Please enter the path to the installation directory: 
if exist %OLD_VUFIND_PATH%\vufind.sh goto oldpathchecked
echo There is no VuFind installation in %OLD_VUFIND_PATH%
goto getoldpath
:oldpathchecked

rem now upgrade the database

echo.
echo.
echo 1) Upgrading MySQL Database
echo We need the credentials of an MySQL admin user to upgrade the database schema

set /p MYSQLADMUSER=MySQL Root User [root]: 
:getroot
set /p MYSQLADMPASS=MySQL Root Password: 
if not "%MYSQLADMPASS%"=="" goto admpassset
echo Please enter a non-blank root password.
goto getroot
:admpassset
if not "%MYSQLADMUSER%"=="" goto admuserset
set MYSQLADMUSER=root
:admuserset

php upgrade\db_RC2to1-0.php %MYSQLADMUSER% %MYSQLADMPASS% %OLD_VUFIND_PATH%

set /p JUNK=Hit ENTER to proceed

echo.
echo 2) configuring vufind.bat and web\conf\config.ini

rem Setup paths for vufind.bat file
echo @set VUFIND_HOME=%VUFIND_PATH%>%VUFIND_PATH%\vufind.bat
echo @call run_vufind.bat %%1 %%2 %%3 %%4 %%5 %%6 %%7 %%8 %%9>>%VUFIND_PATH%\vufind.bat

rem update config.ini with settings from the old version:
php upgrade\config_RC2to1-0.php %OLD_VUFIND_PATH%

set /p JUNK=Hit ENTER to proceed

@echo off
rem We had to turn echo off above because the PEAR batch file turns it back on.

rem display post-upgrade notes
echo.
echo --------------------------------------------------------------
echo Upgrade finished.  You still need to do some things manually:
echo.
echo 1.) Take a look at file %VUFIND_PATH%\web\conf\config.ini.new
echo and change settings where necessary. If you are happy with it,
echo rename it to config.ini.
echo.
echo 2.) Please check the contents of the file
echo %VUFIND_PATH%\httpd-vufind.conf
echo and add it to your Apache configuration.
echo.
echo 3.) Check the SolrMarc configuration in the import directory
echo and reindex all of your records.
echo.
echo 4.) Obviously, if you have customized code, templates or index
echo fields in your previous installation, you will need to merge
echo your changes with the new code.  Feel free to ask questions on
echo the vufind-tech mailing list if you need help!
echo.
echo For the latest notes on upgrading, see the online documentation
echo at http://www.vufind.org/wiki/migration_notes

:end