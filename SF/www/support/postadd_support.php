<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

if (!$support_category_id) {
	$support_category_id=100;
}

if (!user_isloggedin()) {
	$user=100;
	if (!$user_email) {
		//force them to fill in user_email if they aren't logged in
		exit_error('ERROR','Go Back and fill in the user_email address or login so that we know what your email is');
	}
} else {
	$user=user_getid();
	//use their user_name if they are logged in
	// LJ No alias on CodeX $user_email=user_getname().'@'.$GLOBALS['sys_users_host'];
	$user_email=user_getemail($user);
}

if (!$group_id || !$summary || !$details) {
	exit_error('Missing Info','Go Back and fill in all the information requested');
}

$sql="INSERT INTO support (priority,close_date,group_id,support_status_id,support_category_id,submitted_by,assigned_to,open_date,summary) ".
	"VALUES ('5','0','$group_id','1','$support_category_id','$user','100','".time()."','".htmlspecialchars($summary)."')";

$result=db_query($sql);

if (!$result) {
	exit_error('Error','Data insertion failed '.db_error());
} else {

	$support_id=db_insertid($result);

	if ($details != '') {
		//create the first message for this ticket
		$result= support_data_create_message($details,$support_id,$user_email);
		if (!$result) {
			$feedback .= ' Comment Failed ';
		} else {
			$feedback .= ' Comment added to support request ';
			//mail_followup($support_id);
		}
	}

	$feedback .= ' Successfully Added Support Request ';
}

?>
