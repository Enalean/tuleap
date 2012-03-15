@echo off
:: optimizesolr.bat
:: Run an optimize process on the solr index
:: $Id: optimizesolr.bat
setlocal
::Get the current batch file's short path
for %%x in (%0) do set scriptdir=%%~dpsx
for %%x in (%scriptdir%) do set scriptdir=%%~dpsx

if EXIST %scriptdir%SolrMarc.jar goto doit
pushd %scriptdir%..
for %%x in (%CD%) do set scriptdir=%%~sx\
popd

:doit
::echo BatchPath = %scriptdir%
::
if "%SOLRMARC_MEM_ARGS%" EQU ""  set SOLRMARC_MEM_ARGS=-Xms512m -Xmx512m
::
java %SOLRMARC_MEM_ARGS% -Dmarc.source="NONE" -Dsolr.optimize_at_end="true" -jar %scriptdir%SolrMarc.jar %1 

