@echo off
:: marcupdate.bat
:: Program to copy a marc record file, merging in updates and deletions
:: $Id:marcupdate.bat
setlocal
::Get the current batch file's short path
for %%x in (%0) do set scriptdir=%%~dpsx
for %%x in (%scriptdir%) do set scriptdir=%%~dpsx

if EXIST %scriptdir%SolrMarc.jar goto doit
pushd %scriptdir%..
for %%x in (%CD%) do set scriptdir=%%~sx\
popd

:doit

java -Dsolrmarc.main.class="org.solrmarc.marc.MarcMerger" -jar %scriptdir%SolrMarc.jar %1 %2 %3 %4 %5 %6 %7 
