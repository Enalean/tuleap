#!/usr/bin/php -q
<?php
/**
 * Copyright 2005 GForge, LLC http://gforge.org/
 * Copyright Xerox Corporation, Codendi Team, 2009. All rights reserved
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/*
 * Tuleap Command-line Interface main script
 *
 * This script parses command line parameters and passes control to the specified module
 * files.
 *
 * Portion of this file is inspired from the  GForge Command-line Interface
 * contained in GForge.
 */

/**** CONFIGURATION SECTION ****/

/**
 * Directory where common include files and module scripts are located (use trailing slash)
 */
define("CODENDI_CLI_DIR", dirname(__FILE__)."/include/");

// Will be used if no other way to set host are valid:
// * --host on command line
// * TULEAP_WSDL environment variable
// * ~/.codendirc
define("WSDL_URL", "%wsdl_domain%/soap/codendi.wsdl.php?wsdl");

/**** END OF CONFIGURATION SECTION ****/

$CLI_VERSION = "1.5.5";

error_reporting(E_ALL);
ini_set('default_socket_timeout', 3600);
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);

/* Include common files */
require_once(CODENDI_CLI_DIR."common.php");	// Common functions, variables and defines
require_once(CODENDI_CLI_DIR."CodendiSOAP.class.php");	// Codendi SOAP wrapper
require_once(CODENDI_CLI_DIR."Log.class.php");	// Logging class
require_once(CODENDI_CLI_DIR."CLI_ModuleFactory.class.php");

// Global logging object

$function_index = 0;		// Points to the position where the information about which function to execute begins

/* Parse the parameters and options passed to the main script */
$log_level = 0;
$display_help = false;
$host = '';
$soap = new CodendiSOAP();

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
		echo "Tuleap Command Line Interface: version ".$CLI_VERSION."\n";
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
    else if ($argv[$i] == "--retry") {
        $i++;
        if (isset($argv[$i]) && !preg_match("/^-/", $argv[$i])) {
            $soap->setMaxRetry(intval($argv[$i]));
        } else {
            echo "You should give a valid value for '--retry' parameter.\n";
            $display_help = true;
        }
    }

	// Unknown parameter
	else {
		exit_error('Unknown parameter: "'.$argv[$i].'"');
	}
}

// If no host define on command line, try to get it from environment
if (!$host && isset($_ENV['TULEAP_WSDL'])) {
    $host = $_ENV['TULEAP_WSDL'];
}
if (!$host && isset($_ENV['CODENDI_WSDL'])) {
    echo PHP_EOL. "/!\ WARNING /!\ You are using CODENDI_WSDL which is a deprecated env variable. Please use TULEAP_WSDL instead.". PHP_EOL . PHP_EOL;
    $host = $_ENV['CODENDI_WSDL'];
}

if ($host) {
    $soap->setWSDLString($host);
}

$LOG = new Log();
$modules = new CLI_ModuleFactory(CODENDI_CLI_DIR."modules/");

if ($display_help || !$function_index) {		// No function was specified. Show the help.
	display_help($modules, $soap);
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
exit(0);



/////////////////////////////////////////////////////////////////////////////
/**
 * display_help - Show the help string
 */
function display_help($modules, $soap) {
	echo <<<EOT
Syntax: php tuleap.php [options] [module name] [function] [parameters]
* Options:
    -h or --help    Display this screen
    --version       Display the software version
    -v              Verbose
    --host          URL of your server's WSDL
    --retry N       If API call fails due to a network issue, re-issue call
                    N times (Default: 0). Safe with Read actions (gets) use
                    it carefully with write (add/update/delete) ones.

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
    echo "Currently using WSDL from ".$soap->getWSDLString()."\n";
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
