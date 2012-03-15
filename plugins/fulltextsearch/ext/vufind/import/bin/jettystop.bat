@echo off
:: A simple script to start jetty given the default configuration of solr for solrmarc
:: $Id: jettystart.bat

setlocal enabledelayedexpansion

::Get the current batch file's short path
for %%x in (%0) do set scriptdir=%%~dpsx
for %%x in (%scriptdir%) do set scriptdir=%%~dpsx
set solrmarcdir=%scriptdir%
if EXIST %solrmarcdir%SolrMarc.jar goto doit
pushd %scriptdir%..
for %%x in (%CD%) do set solrmarcdir=%%~sx\
popd

:doit

for /f "usebackq delims=" %%g in (`%scriptdir%getdefaultconfig`) do set config=%%g

if "%1" NEQ "" call :set_arg %1

if "%JETTY_HOME%" NEQ "" goto :have_jetty_home
set JETTY_HOME=%solrmarcdir%jetty

:have_jetty_home

if "%JETTY_SOLR_HOME%" NEQ "" goto :have_solr_home
if EXIST "%solrmarcdir%%config%" ( 
pushd %solrmarcdir%
for /f "usebackq tokens=3 delims= " %%H in (`findstr /B "solr.path" %solrmarcdir%%config%`) do set JETTY_SOLR_HOME=%%~fH
popd
)

if "%JETTY_SOLR_HOME%" == "REMOTE" goto :get_solr_home 
if "%JETTY_SOLR_HOME%" NEQ "" goto :have_solr_home
:get_solr_home
set JETTY_SOLR_HOME=%JETTY_HOME%/solr

:have_solr_home

if "%JETTY_SOLR_PORT%" NEQ "" goto :have_solr_port 

if EXIST "%scriptdir%%config%" ( 
for /f "usebackq tokens=4 delims=:/= " %%G in (`findstr "^solr.hosturl" %solrmarcdir%%config%`) do set JETTY_SOLR_PORT=%%G 
)
if "%JETTY_SOLR_PORT%" NEQ "" goto :have_solr_port 
set JETTY_SOLR_PORT=8983

:have_solr_port

set baseconfig=%config:~0,-11%
set outfile=%solrmarcdir%%baseconfig%.jetty.out

echo Stopping jetty webserver 
echo  based on SolrMarc config file: %config% 
echo  using solr home of %JETTY_SOLR_HOME%
echo  using port %JETTY_SOLR_PORT% 

set stopport=
set stopkey=
for /f "usebackq delims=" %%a in (`type %outfile%`) do (
if "!stopport!" == ""  ( set stopport=%%a ) else if "!stopkey!" == ""  set stopkey=%%a
if "!stopkey!" NEQ ""  goto gotstopport
)
:gotstopport

pushd %JETTY_HOME%

java -DSTOP.PORT=%stopport% %stopkey% -jar %JETTY_HOME%\start.jar --stop 

GOTO :done

:set_arg

set arg=%1
if "%arg:~-17%" == "config.properties" set config=%arg%

goto :eof


endlocal
:done
