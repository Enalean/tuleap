<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/*
	redirect to proper hostname to get around certificate problem on IE 5
*/

// Defines all of the Source Forge hosts, databases, etc.
// This needs to be loaded first becuase the lines below depend upon it.

require (getenv('SF_LOCAL_INC_PREFIX').'/etc/local.inc');

list($host,$port) = explode(':', $HTTP_HOST);
if (($host != $GLOBALS['sys_default_domain']) && ($host != 'localhost')) {
	if ($SERVER_PORT == '443') {
		header ("Location: https://".$GLOBALS['sys_default_domain']."$REQUEST_URI");
	} else {
		header ("Location: http://".$GLOBALS['sys_default_domain'].":$port$REQUEST_URI");
	}
	exit;
}

//library to determine browser settings
require('browser.php');

//base error library for new objects
require('Error.class');

// HTML layout class, may be overriden by the Theme class
require('Layout.class');

$HTML = new Layout();

//various html utilities
require('utils.php');

//database abstraction
require('database.php');

//security library
require('session.php');

//user functions like get_name, logged_in, etc
require('user.php');

//group functions like get_name, etc
require('Group.class');

//Project extends Group and includes preference accessors
require('Project.class');

//library to set up context help
require('help.php');

//exit_error library
require('exit.php');

//various html libs like button bar, themable
require('html.php');

//left-hand nav library, themable
require('menu.php');

//theme functions like get_themename, etc
require('theme.php');

$sys_datefmt = "Y-M-d H:i";

// #### Connect to db

db_connect();

if (!$conn) {
	print "Could Not Connect to Database".db_error();
	exit;
}

//determine if they're logged in
session_set();

//set up the themes vars
theme_sysinit($sys_themeid);

// OSDN functions and defs
require('osdn.php');

//insert this page view into the database
require('logger.php');

/*

	Timezone must come after logger to prevent messups


*/
//set up the user's timezone if they are logged in
if (user_isloggedin()) {
	putenv('TZ='.user_get_timezone());
} else {
	//just use pacific time as always
}

?>
