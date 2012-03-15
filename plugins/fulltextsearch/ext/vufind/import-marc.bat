@echo off
rem Batch file to start the import of a binary marc file for Solr indexing.
rem
rem VUFIND_HOME
rem     Path to the vufind installation
rem SOLRMARC_HOME
rem     Path to the solrmarc installation
rem JAVA_HOME
rem     Path to the java
rem INDEX_OPTIONS
rem     Options to pass to the JVM

if not "!%1!"=="!!" goto argfound
echo     Usage: %0 c:\path\to\marc.mrc
goto end
:argfound

rem Make sure that environment edits are local and that we have access to the 
rem Windows command extensions.
setlocal enableextensions
if not errorlevel 1 goto extensionsokay
echo Unable to enable Windows command extensions.
goto end
:extensionsokay

rem ##################################################
rem # Set INDEX_OPTIONS
rem #   Tweak these in accordance to your needs
rem # Xmx and Xms set the heap size for the Java Virtual Machine
rem # You may also want to add the following:
rem # -XX:+UseParallelGC
rem # -XX:+AggressiveOpts
rem ##################################################
set INDEX_OPTIONS=-Xms512m -Xmx512m

rem ##################################################
rem # Set SOLRCORE
rem ##################################################
if not "!%SOLRCORE%!"=="!!" goto solrcorefound
set SOLRCORE=biblio
:solrcorefound

rem ##################################################
rem # Set SOLR_HOME
rem ##################################################
if not "!%VUFIND_HOME%!"=="!!" goto vufindhomefound
rem VUFIND_HOME not set -- try to call vufind.bat to 
rem fix the problem before we give up completely
if exist vufind.bat goto usevufindbat
rem If vufind.bat doesn't exist, the user hasn't run install.bat yet.
echo ERROR: vufind.bat does not exist -- could not set up environment.
echo Please run install.bat to correct this problem.
goto end
:usevufindbat
call vufind > nul
if not "!%VUFIND_HOME%!"=="!!" goto vufindhomefound
echo You need to set the VUFIND_HOME environmental variable before running this script.
goto end
:vufindhomefound
if not "!%SOLR_HOME%!"=="!!" goto solrhomefound
set SOLR_HOME=%VUFIND_HOME%\solr
:solrhomefound

rem ##################################################
rem # Set SOLRMARC_HOME
rem ##################################################
if not "!%SOLRMARC_HOME%!"=="!!" goto solrmarchomefound
set SOLRMARC_HOME=%VUFIND_HOME%\import
:solrmarchomefound

rem #####################################################
rem # Build java command
rem #####################################################
if not "!%JAVA_HOME%!"=="!!" goto javahomefound
set JAVA=java
goto javaset
:javahomefound
set JAVA="%JAVA_HOME%\bin\java"
:javaset

rem ##################################################
rem # Set properties file if not already provided
rem ##################################################
if not "!%PROPERTIES_FILE%!"=="!!" goto propertiesfound
set PROPERTIES_FILE=%VUFIND_HOME%\import\import.properties
:propertiesfound

rem ##################################################
rem # Set Command Options
rem ##################################################
set JAR_FILE=%VUFIND_HOME%\import\SolrMarc.jar
set SOLR_JAR_DEF=-Dsolrmarc.solr.war.path=%VUFIND_HOME%\solr\jetty\webapps\solr.war

rem #####################################################
rem # Execute Importer
rem #####################################################
set RUN_CMD=%JAVA% %INDEX_OPTIONS% %SOLR_JAR_DEF% -Dsolr.core.name=%SOLRCORE% -Dsolrmarc.path=%SOLRMARC_HOME% -Dsolr.path=%SOLR_HOME% -Dsolr.solr.home=%SOLR_HOME% %EXTRA_SOLRMARC_SETTINGS% -jar %JAR_FILE% %PROPERTIES_FILE% %1
echo Now Importing %1 ...
echo %RUN_CMD%
%RUN_CMD%

:end

rem We're all done -- close down the local environment.
endlocal