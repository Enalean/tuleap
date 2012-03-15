@echo off
:: indextest2.bat
:: Diagnostic program to show how a set of marc records would be indexed,
:: without actually adding any records to Solr.
:: $Id: indextest2.bat 
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
java %SOLRMARC_MEM_ARGS% -Dmarc.just_index_dont_add="true" -jar %scriptdir%SolrMarc.jar %1 %2 %3 


