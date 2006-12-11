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
 
/*
* Variables passed by parent script:
* - $SOAP: Soap object to talk to the server
* - $PARAMS: parameters passed to this script
* - $LOG: object for logging of events
*/

// function to execute
$function_name = array_shift($PARAMS);		// Pop off the name of the function

switch ($function_name) {
case "login":
	default_do_login();
	break;
case "logout":
	default_do_logout();
	break;
default:
	exit_error("Unknown function name: ".$function_name);
	break;
}

////////////////////////////////////////////////
/**
 * default_do_login - Login in the system
 */
function default_do_login() {
	global $PARAMS, $SOAP, $LOG;
	
	if (get_parameter($PARAMS, "help")) {
		echo <<<EOF
login - Log into CodeX server
Available parameters:
   --username=<username> or -U <username>    Specify the user name
   --password=<password> or -p <password>    Specify the password. If none is entered, it
      will be asked (note that this is the UNIX name of the project)
   --project=<projectname>                   (Optional) Select a project to work on
   --help                                    Show this screen

Example:
   codex login -U john -p doe --project=myproject
EOF;
		return;
	}
	
	$username = get_parameter($PARAMS, array("username", "U"), true);
	$password = get_parameter($PARAMS, array("password", "p"), true);
	$host = get_parameter($PARAMS, "host", true);
	$secure = get_parameter($PARAMS, array("secure", "s"));
	$projectname = get_parameter($PARAMS, "project", true);
	
	// If no username is specified, use the system user name
	if (strlen($username) == 0) {
		if (array_key_exists("USER", $_ENV)) {
			$username = $_ENV["USER"];
		} else {
			exit_error("You must specify the user name with the --username parameter");
		}
	}
	
	// If no password is specified, ask for it
	if (strlen($password) == 0) {
		$password = get_user_input("Password: ", true);
	}
	
	if (strlen($host) > 0) {
		if ($secure) $protocol = "https";
		else $protocol = "http";
		$SOAP->setWSDL($protocol."://".$host."/soap/?wsdl");
	}
	
	// Terminate an existing session (if any)
	$SOAP->endSession();
	
	// try to login in the server
	$result = $SOAP->call("login", array(
		"loginname"	=> $username,
		"passwd"	=> $password
	),false);
	
	// there was an error
	if (($err = $SOAP->getError())) {
		exit_error($err, $SOAP->faultcode);
	}
	
	// Login is OK, $result containts the session hash string
    $session_string = $result['session_hash'];
    $user_id = $result['user_id'];
	$LOG->add("Logged in as user ".$username." (user_id=".$user_id."), using session string ".$session_string);
	echo "Logged in.\n";
	$SOAP->setSessionString($session_string);
	$SOAP->setSessionUser($username);
    $SOAP->setSessionUserID($user_id);
	
	// If project was specified, get project information and store for future use
	if (strlen($projectname) > 0) {
		$group_id = get_group_id($projectname);
		if (!$group_id) {
			exit_error("Project \"".$projectname."\" doesn't exist");
		}
		
		$SOAP->setSessionGroupID($group_id);
		$LOG->add("Using group #".$group_id);
	}
	
	$SOAP->saveSession();
}

/**
 * default_do_logout - Terminate the session
 */
function default_do_logout() {
	global $PARAMS, $SOAP, $LOG;
	
	$SOAP->call("logout");
	if (($error = $SOAP->getError())) {
		exit_error($error, $SOAP->faultcode);
	}

	$SOAP->endSession();
	echo "Session terminated.\n";
}
?>
