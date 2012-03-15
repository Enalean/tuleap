@echo off
:: marcerror.sh
:: Diagnostic program to show look for errors in Marc records.
:: $Id: marcerror.sh 
setlocal
::Get the current batch file's short path
for %%x in (%0) do set scriptdir=%%~dpsx
for %%x in (%scriptdir%) do set scriptdir=%%~dpsx

if EXIST %scriptdir%SolrMarc.jar goto doit
pushd %scriptdir%..
for %%x in (%CD%) do set scriptdir=%%~sx\
popd

:doit
set inarg=%1

set arg1=-
for /f "delims=" %%a in ('echo %inarg% ^| findstr "\.mrc"') do @set arg1=%%a

java -Dsolrmarc.main.class="org.solrmarc.marc.MarcSorter" -jar %scriptdir%SolrMarc.jar %arg1% 
