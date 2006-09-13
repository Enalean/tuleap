#!/usr/bin/php -q
<?php
/**
 * CodeX Command-line Interface
 *
 * Portion of this file is inspired from the  GForge Command-line Interface
 * contained in GForge.
 * Copyright 2005 GForge, LLC
 * http://gforge.org/
 *
 * @version   $Id$
 */

/**
 * CodeX CLI main script
 *
 * This script parses command line parameters and passes control to the specified module
 * files.
 */

/**** CONFIGURATION SECTION ****/

/**
 * Directory where NuSOAP library is located (use trailing slash)
 */
define("NUSOAP_DIR", dirname(__FILE__)."/nusoap/");
/**
 * Directory where common include files and module scripts are located (use trailing slash)
 */ 
define("CODEX_CLI_DIR", dirname(__FILE__)."/include/");
/**
 * URL of your server's WSDL
 */
if (array_key_exists("CODEX_WSDL", $_ENV)) {
	define("WSDL_URL", $_ENV["CODEX_WSDL"]);
} else {
	define("WSDL_URL", "http://codex.xerox.com/soap/index.php?wsdl");
}

/**** END OF CONFIGURATION SECTION ****/

$CLI_VERSION = "0.1";

error_reporting(E_ALL);

/* Include common files */
require_once(NUSOAP_DIR."nusoap.php");		// Main NuSOAP library
require_once(CODEX_CLI_DIR."common.php");	// Common functions, variables and defines
require_once(CODEX_CLI_DIR."CodeXSOAP.class.php");	// CodeX SOAP wrapper
require_once(CODEX_CLI_DIR."Log.class.php");	// Logging class

// This is automatically done by PHP >= 4.3.0
// Code copied from http://ar2.php.net/install.unix.commandline
if (! defined("STDIN") || ! defined("STDOUT") || ! defined("STDERR")) {
    define('STDIN',fopen("php://stdin","r"));
	define('STDOUT',fopen("php://stdout","r"));
	define('STDERR',fopen("php://stderr","r"));
	register_shutdown_function( create_function( '' , 'fclose(STDIN); fclose(STDOUT); fclose(STDERR); return true;' ) );
}

// Global logging object
$LOG = new Log();

$function_index = 0;		// Points to the position where the information about which function to execute begins

/* Parse the parameters and options passed to the main script */
for ($i = 1; $i <= $argc-1; $i++) {
	// Show user the help screen
	if ($argv[$i] == "--help" || $argv[$i] == "-h") {
		display_help();
		exit(0);
	}

	// Verbose
	else if ($argv[$i] == "--verbose" || $argv[$i] == "-v") {
		// Increase verbose level
		$LOG->setLevel(1);
	}
    
    // Version
	else if ($argv[$i] == "--version" || $argv[$i] == "-version") {
		echo "CodeX Command Line Interface: version ".$CLI_VERSION."\n";
        exit(0);
	}
	
    // Interactive
	else if ($argv[$i] == "--interactive" || $argv[$i] == "-i") {
		// Set the interactive mode
		$interactive = true;
	}
    
	// Not a parameter for the main script (does not start with "-").
	// Then, it must be a name of a module or a name of a function
	else if (!preg_match("/^-/", $argv[$i])) {
		$function_index = $i;
		break;
	}
	
	// Unknown parameter
	else {
		exit_error("Unknown parameter: \"".$argv[$i]."\"");
	}
}

if (!$function_index) {		// No function was specified. Show the help.
	display_help();
	exit(0);
}

// Get the name of the module or the function to execute
$name = trim($argv[$function_index]);

// Now, check if the name corresponds to a module. It corresponds to a module
// if there exists a directory with that name. In that case, execute the "default.php"
// script in that directory
if (is_dir(CODEX_CLI_DIR."modules/".$name)) {		// We've found a module with that name
	$script = CODEX_CLI_DIR."modules/".$name."/default.php";
} else {
	$script = CODEX_CLI_DIR."modules/default.php";
}

if (!file_exists($script)) {
	exit_error("Could not find file ".$script);
}

// At this point, we know which script we should execute.
// Now we need to prepare the environment for the script (common variables,
// pass the parameters, etc) 

// Set up the parameters for the script... we don't need to pass that script the parameters that were
// passed to THIS script
$PARAMS = array_slice($argv, $function_index);
$SOAP = new CodeXSOAP();

// Pass control to the appropiate script
include($script);

// End script
echo "\n";
exit(0);



/////////////////////////////////////////////////////////////////////////////
/**
 * display_help - Show the help string
 */
function display_help() {
	echo <<<EOT
Syntax:
codex [options] [module name] [function] [parameters]
* Options:
    -h or --help    Display this screen
    --version       Display the software version
    -v              Verbose

Available modules:
   * tracker
   
Available functions for the default module:
   * login: Begin a session with the server.
   * logout: Terminate a session

EOT;
}
?>

