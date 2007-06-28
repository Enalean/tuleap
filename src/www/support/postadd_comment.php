<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

$changes = array();
$changed = false;

if (!user_isloggedin()) {
	if (!$user_email) {
		//force them to fill in user_email if they aren't logged in
		exit_error('ERROR','Go Back and fill in the user_email address or login');
	}
} else {
	//use user login name instead of email if they are logged in
	$user_email=user_getname(user_getid());
}

// Add a new comment if there is one
if ($details != '') {

	$result= support_data_create_message($details,$support_id,$user_email);
	$changes['details']['add'] = stripslashes($details);
	$changed = true;

	if (!$result) {
		$feedback .= ' Comment Failed ';
	} else {
		$feedback .= ' Comment added to support request ';
	}
}

if (!$changed) {
    $feedback .= ' Nothing Done ';
}
?>
