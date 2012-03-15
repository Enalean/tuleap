@echo off

rem Begin Script
cls
echo Welcome to the VuFind Setup Script.
echo This will setup the MySQL Database as well as the necessary system libraries
echo.

rem Make sure that environment edits are local and that we have access to the 
rem Windows command extensions.
setlocal enableextensions
if not errorlevel 1 goto extensionsokay
echo Unable to enable Windows command extensions.
goto end
:extensionsokay

rem Make sure the batch file is running from the appropriate directory.
if exist mysql.sql goto inrightplace
echo You must run install.bat from the directory where you extracted VuFind!
goto end
:inrightplace

rem Setup paths for vufind.bat file
echo @set VUFIND_HOME=%CD%>vufind.bat
echo @call run_vufind.bat %%1 %%2 %%3 %%4 %%5 %%6 %%7 %%8 %%9>>vufind.bat

rem MySQL Database Setup
echo The first step is to install the MySQL Database.
echo.

rem Try to detect the location of the MySQL binaries and prompt for user help if necessary
rem (needed since MySQL binaries are not on the search path by default).
set MYSQLINSTALLDIR=c:\Program Files\MySQL\MySQL Server
for /d %%a in ("c:\Program Files\MySQL\*") do set MYSQLINSTALLDIR=%%a
:checkmysql
if exist "%MYSQLINSTALLDIR%\bin\mysql.exe" goto foundmysql
echo Could not find %MYSQLINSTALLDIR%\bin\mysql.exe!
echo.
set /p MYSQLINSTALLDIR=Enter the path to your MySQL installation (i.e. C:\Program Files\MySQL\MySQL Server 5.0):
goto checkmysql
:foundmysql

rem Prompt for Database values
set /p MYSQLDB=New Database Name [vufind]:
set /p MYSQLNEWUSER=New Database User [vufind]:
set /p MYSQLNEWPASS=New User Password:
echo.
set /p MYSQLHOST=MySQL Host [localhost]:
set /p MYSQLUSER=MySQL Root User [root]:
set /p MYSQLPASS=MySQL Root Password:
echo.

rem Set defaults if selected
if not "!%MYSQLDB%!"=="!!" goto skipdbndefault
set MYSQLDB=vufind
:skipdbndefault
if not "!%MYSQLNEWUSER%!"=="!!" goto skipdbudefault
set MYSQLNEWUSER=vufind
:skipdbudefault
if not "!%MYSQLHOST%!"=="!!" goto skipdbhdefault
set MYSQLHOST=localhost
:skipdbhdefault
if not "!%MYSQLUSER%!"=="!!" goto skipdbrdefault
set MYSQLUSER=root
:skipdbrdefault

rem Process creating mysql user and database
"%MYSQLINSTALLDIR%\bin\mysqladmin" -h %MYSQLHOST% -u %MYSQLUSER% -p%MYSQLPASS% create %MYSQLDB%
"%MYSQLINSTALLDIR%\bin\mysql" -h %MYSQLHOST% -u %MYSQLUSER% -p%MYSQLPASS% -e "GRANT SELECT,INSERT,UPDATE,DELETE ON %MYSQLDB%.* TO '%MYSQLNEWUSER%'@'%MYSQLHOST%' IDENTIFIED BY '%MYSQLNEWPASS%' WITH GRANT OPTION"
"%MYSQLINSTALLDIR%\bin\mysql" -h %MYSQLHOST% -u %MYSQLUSER% -p%MYSQLPASS% -e "FLUSH PRIVILEGES"
"%MYSQLINSTALLDIR%\bin\mysql" -h %MYSQLHOST% -u %MYSQLUSER% -p%MYSQLPASS% -D %MYSQLDB% < mysql.sql

rem Rename the vufind.ini file to match the database name
if "%MYSQLDB%"=="vufind" goto skipdbrename
move web\conf\vufind.ini web\conf\%MYSQLDB%.ini
:skipdbrename

echo The MySQL Database has now been created.
echo.
echo Don't forget to edit web/conf/config.ini to include the correct username and password!
echo.
pause
echo.
echo Now installing the System Libraries ...

rem Install PEAR Packages (assumes PEAR is available on search path)
@call pear upgrade pear
@call pear install --onlyreqdeps DB
@call pear install --onlyreqdeps DB_DataObject
@call pear install --onlyreqdeps Structures_DataGrid-beta
@call pear install --onlyreqdeps Structures_DataGrid_DataSource_DataObject-beta
@call pear install --onlyreqdeps Structures_DataGrid_DataSource_Array-beta
@call pear install --onlyreqdeps Structures_DataGrid_Renderer_HTMLTable-beta
@call pear install --onlyreqdeps HTTP_Client
@call pear install --onlyreqdeps HTTP_Request
@call pear install --onlyreqdeps Log
@call pear install --onlyreqdeps Mail
@call pear install --onlyreqdeps Mail_Mime
@call pear install --onlyreqdeps Net_SMTP
@call pear install --onlyreqdeps Pager
@call pear install --onlyreqdeps XML_Serializer-beta
@call pear install --onlyreqdeps Console_ProgressBar-beta
@call pear install --onlyreqdeps File_Marc-alpha
@call pear channel-discover pear.horde.org
@call pear install horde/yaml

@echo off
rem We had to turn echo off above because the PEAR batch file turns it back on.
echo VuFind Setup is now Complete

rem Display a message about installing Smarty if we can't find it on the system:
if exist "c:\Program Files\PHP\PEAR\Smarty\Smarty.class.php" goto end
echo.
echo Don't forget to install Smarty if you haven't already!  See notes here:
echo http://www.vufind.org/wiki/installation_windows
pause

:end

rem We're all done -- close down the local environment.
endlocal