@echo off
:: getfromsolr.bat
:: Program to extract one or more MARC records from a solr index 
:: $Id: getfromsolr.bat 
::Get the current batch file's short path
setlocal
::
for %%x in (%0) do set scriptdir=%%~dpsx
for %%x in (%scriptdir%) do set scriptdir=%%~dpsx

if EXIST %scriptdir%SolrMarc.jar goto doit
pushd %scriptdir%..
for %%x in (%CD%) do set scriptdir=%%~sx\
popd

:doit
::
if "%1" EQU "" goto usage
::
set id=
set url=
set query=
set field=
set config=
::
if "%1" NEQ "" call :set_arg %1
if "%2" NEQ "" call :set_arg %2
if "%3" NEQ "" call :set_arg %3
::echo id=%id%
::echo url=%url%
::
if "%query%" == "" if "%id%" NEQ "" set query=id:%id%
::echo query=%query%
::
if "%url%" == "" java -Dsolrmarc.main.class=org.solrmarc.marc.SolrReIndexer -jar %scriptdir%SolrMarc.jar %config% "%query%" "%field%" 2> NUL
if "%url%" NEQ "" java -Dsolrmarc.main.class="org.solrmarc.solr.RemoteSolrSearcher" -jar %scriptdir%SolrMarc.jar %url% "%query%" "%field%"
::
goto done
::
:usage
echo Usage: %0 field:term (field_name_containing_marc_record) 
goto done
::
:set_arg
::
set arg=%1
if "%arg:~-17%" == "config.properties" goto setconfig
if "%arg:~0,4%" == "http" goto set_url
if "%id%" NEQ "" goto :have_query
if "%query%" NEQ "" goto :have_query
for /f "tokens=1,2 delims=:" %%g in ("%arg%") do set a1=%%g&set a2=%%h
if "%a2%" == "" set id=%a1%
if "%a2%" NEQ "" set query=%arg%
goto :eof
::
:set_url
set url=%arg%
goto :eof
::
:setconfig
set config=%arg%
goto :eof
::
:have_query
set field=%arg%
goto :eof
::
:done
