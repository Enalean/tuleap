<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

function exit_error($title,$text) {
	GLOBAL $HTML;
	$HTML->header(array('title'=>'Exiting with Error'));
	print '<H2><font color="#FF3333">'.$title.'</font></H2><P>'.$text;
	$HTML->footer(array());
	exit;
}

function exit_permission_denied() {
	exit_error('Permission Denied','This project\'s administrator will have to grant you permission to view this page.');
}

function exit_not_logged_in() {
	global $REQUEST_URI;
	//instead of a simple error page, now take them to the login page
	header ("Location: /account/login.php?return_to=".urlencode($REQUEST_URI));
	//exit_error('Not Logged In','Sorry, you have to be <A HREF="/account/login.php">logged in</A> to view this page.');
}

function exit_no_group() {
	exit_error('Error - Choose a Group','ERROR - No group_id was chosen.');
}

function exit_missing_param() {
	exit_error('Error - Missing Params','ERROR - Missing Required Parameteres.');
}

?>
