@echo off
:: $Id: index_file.sh 17 2008-06-20 14:40:13Z wayne.graham $
::
:: Batch script to start the import of a binary marc file for Solr indexing.
::
:: VUFIND_HOME
::	Path to the vufind installation
:: SOLRMARC_HOME
::	Path to the solrmarc installation
:: JAVA_HOME
::	Path to the java
:: INDEX_OPTIONS
::	Options to pass to the JVM
::
setlocal

set E_BADARGS=65
set EXPECTED_ARGS=1

if "$1" NEQ "" goto havearg
  for %%g in (%0) do set basename=%%~nxg
  echo "    Usage: %basename% .\path\to\marc.mrc"
  goto done 

:havearg

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: Set INDEX_OPTIONS
:: 	Tweak these in accordance to your needs
:: Xmx and Xms set the heap size for the Java Virtual Machine
:: You may also want to add the following:
:: -XX:+UseParallelGC
:: -XX:+AggressiveOpts
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
set INDEX_OPTIONS=-Xms512m -Xmx512m


::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: Set SOLRCORE
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
if "%SOLRCORE%" EQU "" set SOLRCORE="biblio"


::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: Set SOLR_HOME
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
if "%VUFIND_HOME%" NEQ "" goto havevufindhome
echo You need to set the VUFIND_HOME environmental variable before running this script.
goto done

:havevufindhome
if "%SOLR_HOME%" NEQ "" goto havesolrhome
set SOLR_HOME=%VUFIND_HOME%\solr

:havesolrhome

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: Set SOLRMARC_HOME
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
if "%SOLRMARC_HOME%" EQU "" set SOLRMARC_HOME=%VUFIND_HOME%\import

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: Build java command
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
set JAVA=java
if "%JAVA_HOME%" NEQ "" set JAVA="%JAVA_HOME%\bin\java"


::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: Set Command Options
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
set JAR_FILE="%VUFIND_HOME%\import\@CUSTOM_JAR_NAME@"
set PROPERTIES_FILE="vufind_config.properties"
set ERROR_LOG="import\error-log"
set IMPORT_LOG="import\import-log"
set SOLRWARLOCATIONORJARDIR=%VUFIND_HOME%\solr\jetty\webapps\solr.war
set SOLR_JAR_DEF=@SOLR_JAR_DEF@

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: Execute Importer
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

pushd %SOLRHOME%
echo "Now Importing %1 ..."
%JAVA% %INDEX_OPTIONS% %SOLR_JAR_DEF% -Dsolr.core.name=%SOLRCORE% -Dsolrmarc.path=%SOLRMARC_HOME% -Dsolr.path=%SOLR_HOME% -jar %JAR_FILE% %PROPERTIES_FILE% %1 > %IMPORT_LOG% 2> %ERROR_LOG%
popd

:done
endlocal
