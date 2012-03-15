<?php
// Include this file to your php unit test.
//
// This add your web folder to the include path list (see also http://us3.php.net/manual/en/function.include.php) and
//include the absolute path. After that, php can find your class you would like to test, by simply adding the relative
// path starting from the folder web plus the name of class file you would to test, e.g.:
// Suposing the class you would to test is under 'web/sys/exampleClass.php.
// Write a unit test with following line:
//
// include_once '../../prepend.inc.php'
//
$actualPath = dirname(__FILE__);
$pathToWeb = str_replace("/tests/web", "/web", $actualPath);
$includePaths = explode(PATH_SEPARATOR, get_include_path());
$includePaths[] = realpath($pathToWeb);
$includePaths[] = realpath($pathToTestConfigurationFiles);
$includePaths = array_unique($includePaths);
set_include_path(implode(PATH_SEPARATOR, $includePaths));

// This is where you have to out the test configuration files.
// Use the following line with your unit test if you like to simulate different 
// configurations:
// ...
// $myConfigurationFile = $_SESSION['pathToTestConfigurationFiles'] . "/config.ini";
// ...

$pathToTestConfigurationFiles = $actualPath . "/conf";
$_SESSION['pathToTestConfigurationFiles'] = $pathToTestConfigurationFiles
?>
