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
	print '<H2><span class="feedback">'.$title.'</span></H2><P>'.$text;
	$HTML->footer(array());
	exit;
}

function exit_permission_denied() {
    global $feedback;
    exit_error('Permission Denied','You are not granted sufficient permission to perform this operation.<p>'.$feedback);
}

function exit_not_logged_in() {
    global $REQUEST_URI;
    //instead of a simple error page, now take them to the login page
    header ("Location: /account/login.php?return_to=".urlencode($REQUEST_URI));
    //exit_error('Not Logged In','Sorry, you have to be <A HREF="/account/login.php">logged in</A> to view this page.');
}

function exit_no_group() {
    global $feedback;
    exit_error('Error - Choose a Project','ERROR - No group_id was chosen.<p>'.$feedback);
}

function exit_missing_param() {
    global $feedback;
    exit_error('Error - Missing Parameters','<p>'.$feedback);
}

?>
