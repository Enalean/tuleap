@echo off
:: A simple script to start jetty given the default configuration of solr for solrmarc
:: $Id: jettystart.bat

setlocal

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

echo jetty solr home = %JETTY_SOLR_HOME%

if "%JETTY_SOLR_HOME%" == "REMOTE" goto :get_solr_home 
if "%JETTY_SOLR_HOME%" NEQ "" goto :have_solr_home
:get_solr_home
set JETTY_SOLR_HOME=%JETTY_HOME%/solr

:have_solr_home

if "%JETTY_SOLR_PORT%" NEQ "" goto :have_solr_port 

if EXIST "%solrmarcdir%%config%" ( 
for /f "usebackq tokens=4 delims=:/= " %%G in (`findstr "^solr.hosturl" %solrmarcdir%%config%`) do set JETTY_SOLR_PORT=%%G 
)
if "%JETTY_SOLR_PORT%" NEQ "" goto :have_solr_port 
set JETTY_SOLR_PORT=8983

:have_solr_port
if "%JETTY_MEM_ARGS%" == ""  set JETTY_MEM_ARGS=-Xms512m -Xmx512m
if "%JETTY_MEM_ARGS:0,1%" == "@"  set JETTY_MEM_ARGS=-Xmx256m

set baseconfig=%config:~0,-11%
set outfile=%solrmarcdir%%baseconfig%.jetty.out

echo Starting jetty webserver 
echo  based on SolrMarc config file: %config% 
echo  using solr home of %JETTY_SOLR_HOME%
echo  using port %JETTY_SOLR_PORT% 
echo  writing output to %outfile%

pushd %JETTY_HOME%

move /Y %outfile% %outfile%.bak > NUL 2>&1
start /B java %JETTY_MEM_ARGS% -DSTOP.PORT=0 -Dsolr.solr.home="%JETTY_SOLR_HOME%" -Djetty.port=%JETTY_SOLR_PORT% -jar start.jar > %outfile% 2>&1

:: sleep for 2 seconds
ping 1.1.1.1 -n 2 -w 1000 > NUL 2>&1

endlocal
GOTO :done

:set_arg

set arg=%1
if "%arg:~-17%" == "config.properties" set config=%arg%

goto :eof

:done
