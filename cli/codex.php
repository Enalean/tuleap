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
 */

/**
 * CodeX CLI main script
 *
 * This script parses command line parameters and passes control to the specified module
 * files.
 */

/**** CONFIGURATION SECTION ****/

/**
 * Directory where common include files and module scripts are located (use trailing slash)
 */ 
define("CODEX_CLI_DIR", dirname(__FILE__)."/include/");
/**
 * URL of your server's WSDL
 */
if (array_key_exists("CODEX_WSDL", $_ENV)) {
	$host = $_ENV["CODEX_WSDL"];
} else {
	$host = "%wsdl_domain%/soap/codex.wsdl.php?wsdl";
}

/**** END OF CONFIGURATION SECTION ****/

$CLI_VERSION = "0.5.0";

error_reporting(E_ALL);

/* Include common files */
require_once(CODEX_CLI_DIR."common.php");	// Common functions, variables and defines
require_once(CODEX_CLI_DIR."CodeXSOAP.class.php");	// CodeX SOAP wrapper
require_once(CODEX_CLI_DIR."Log.class.php");	// Logging class
require_once(CODEX_CLI_DIR."CLI_ModuleFactory.class.php");

// Global logging object

$function_index = 0;		// Points to the position where the information about which function to execute begins

/* Parse the parameters and options passed to the main script */
$log_level = 0;
$display_help = false;

for ($i = 1; $i <= $argc-1; $i++) {
	// Show user the help screen
	if ($argv[$i] == "--help" || $argv[$i] == "-h") {
		$display_help = true;
	}

	// Verbose
	else if ($argv[$i] == "--verbose" || $argv[$i] == "-v") {
		// Increase verbose level
        $log_level = 1;
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
	
    else if ($argv[$i] == "--host") {
        $i++;
        if (isset($argv[$i]) && !preg_match("/^-/", $argv[$i])) {
            $host = $argv[$i];
        } else {
            echo "You should give a valid url for host.\n";
            $display_help = true;
        }
    }
	// Unknown parameter
	else {
		exit_error('Unknown parameter: "'.$argv[$i].'"');
	}
}

define("WSDL_URL", $host);
$LOG = new Log();
$soap = new CodeXSOAP();
$modules =& new CLI_ModuleFactory(CODEX_CLI_DIR."modules/");

if ($display_help || !$function_index) {		// No function was specified. Show the help.
	display_help($modules);
	exit(0);
}
$LOG->setLevel($log_level);

// Get the name of the module or the function to execute
$name = trim($argv[$function_index]);
$params = array_slice($argv, $function_index);

// Now, check if the name corresponds to a module.
if (!$modules->exist($name)) {
    $name = 'default';
} else {
    array_shift($params); //consume the module name
}
$module =& $modules->getModule($name);

if (!$module) {
	exit_error("Could not find module ".$name);
}

// At this point, we know which script we should execute.
// Now we need to prepare the environment for the script (common variables,
// pass the parameters, etc) 

// Set up the parameters for the script... we don't need to pass that script the parameters that were
// passed to THIS script



// Pass control to the appropiate script
$module->execute($params);

// End script
echo "\n";
exit(0);



/////////////////////////////////////////////////////////////////////////////
/**
 * display_help - Show the help string
 */
function display_help(&$modules) {
	echo <<<EOT
Syntax:
codex [options] [module name] [function] [parameters]
* Options:
    -h or --help    Display this screen
    --version       Display the software version
    -v              Verbose
    --host          URL of your server's WSDL


EOT;
    $all_modules = $modules->getAllModules();
    $default_module = '';
    if (isset($all_modules['default'])) {
        $actions = $all_modules['default']->getAllActions();
        if (count($actions)) {
            $default_module .= "Available functions for the default module:\n";
            ksort($actions);
            foreach($actions as $action) {
                $default_module .= "   * ". $action->getName() ."\n";
                $default_module .= "     ". $action->getDescription() ."\n";
            }
            $default_module .= "\n";
        }
        unset($all_modules['default']);
    }
    if (count($all_modules)) {
        echo "Available modules:\n";
        ksort($all_modules);
        foreach($all_modules as $module) {
            echo "   * ". $module->getName() ."\n";
            echo "     ". $module->getDescription() ."\n";
        }
        echo "\n";
    }
    echo $default_module;
    echo "Currently using WSDL from ".WSDL_URL."\n";
}
/*
Available modules:
   * tracker
   * frs
   
Available functions for the default module:
   * login: Begin a session with the server.
   * logout: Terminate a session

EOT;
*/
?>

