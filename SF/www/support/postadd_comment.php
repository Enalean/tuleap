<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

if (!user_isloggedin()) {
	if (!$user_email) {
		//force them to fill in user_email if they aren't logged in
		exit_error('ERROR','Go Back and fill in the user_email address or login');
	}
} else {
	//use user login name instead of email if they are logged in
	// LJ No alias on CodeX $user_email=user_getname().'@'.$GLOBALS['sys_users_host'];
	$user_email=user_getname(user_getid());
}

if ($details != '') {
	//create the first message for this ticket
	$result= support_data_create_message($details,$support_id,$user_email);
	if (!$result) {
		$feedback .= ' Comment Failed ';
	} else {
		$feedback .= ' Comment added to support request ';
	}
}


?>
