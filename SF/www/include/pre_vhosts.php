<?php

//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
// This is a restricted pre.php file for use by the virtual project web server
// It gives access to user and group information, opens up a db connection
// and sets the time zone.

// Defines all of the CodeX settings first (hosts, databases, etc.)
require (getenv('SF_LOCAL_INC_PREFIX').'/etc/codex/conf/local.inc');

//library to determine browser settings
require('browser.php');

//base error library for new objects
require('Error.class');

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

// #### Connect to db

db_connect();

if (!$conn) {
	print "Could Not Connect to Database".db_error();
	exit;
}

//determine if they're logged in
session_set();

//set up the user's timezone if they are logged in
if (user_isloggedin()) {
	putenv('TZ='.user_get_timezone());
} else {
	//just use pacific time as always
}

//set up the group (project information)
$pieces = explode('.', $HTTP_HOST);
$group_name = $pieces[0];
$res_grp=db_query("SELECT * FROM groups WHERE unix_group_name='$group_name'");



if (db_numrows($res_grp) < 1) {
    //group was not found
    echo db_error();
    exit_error("Invalid Project '$group_name'","That group does not exist.");

} else {

    //set up the group_id and the project object
    $group_id=db_result($res_grp,0,'group_id');

    $project=new Project($group_id);

}
