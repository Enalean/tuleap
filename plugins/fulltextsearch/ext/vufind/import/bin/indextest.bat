@echo off
:: indextest.bat
:: Diagnostic program to show how a set of marc records would be indexed,
:: without actually adding any records to Solr.
:: $Id: indextest.bat 
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

java -Dsolrmarc.main.class="org.solrmarc.marc.MarcPrinter" -jar %scriptdir%SolrMarc.jar index %1 %2 %3
