@echo off
:: setdefaultconfig.bat
:: Set the name of the default config file to use.
:: $Id: setdefaultconfig.bat
setlocal
::Get the current batch file's short path
for %%x in (%0) do set scriptdir=%%~dpsx
for %%x in (%scriptdir%) do set scriptdir=%%~dpsx

if EXIST %scriptdir%SolrMarc.jar goto doit
pushd %scriptdir%..
for %%x in (%CD%) do set scriptdir=%%~sx\
popd

:doit

if "%1" NEQ "" call :set_arg %1
if "%config%" NEQ "" goto :do_it
echo  Usage: setdefaultconfig your_config.properties
goto :done

:do_it
java -Dsolrmarc.main.class="org.solrmarc.tools.PropertyFileFetcher" -jar %scriptdir%SolrMarc.jar JarUtils.jar %scriptdir%

java -classpath %scriptdir%JarUtils.jar JarUpdate %scriptdir%SolrMarc.jar "META-INF/MANIFEST.MF" "Default-Config-File: %config%" > NUL
echo Default configuration in SolrMarc.jar set to %config%

del /q %scriptdir%JarUtils.jar

endlocal
GOTO :done

:set_arg

set arg=%1
if "%arg:~-17%" == "config.properties" set config=%arg%

goto :eof

:done
