<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    // Initial db and session library, opens session
require "vars.php";
require('account.php');
session_require(array('isloggedin'=>'1'));

if ($insert_group_name && $group_id && $rand_hash && $form_full_name && $form_unix_name) {
	/*
		check for valid group name
	*/
	if (!account_groupnamevalid($form_unix_name)) {
		exit_error("Invalid Group Name",$register_error);
	}
	/*
		See if it's taken already
	*/
	if (db_numrows(db_query("SELECT group_id FROM groups WHERE unix_group_name LIKE '$form_unix_name'")) > 0) {
		exit_error("Group Name Taken","That group name already exists.");
	}
	/*
		Hash prevents them from updating a live, existing group account
	*/
	$sql="UPDATE groups SET unix_group_name='". strtolower($form_unix_name) ."', group_name='$form_full_name', ".
		"http_domain='$form_unix_name.$GLOBALS[sys_default_domain]' ".
		"WHERE group_id='$group_id' AND rand_hash='__$rand_hash'";
	$result=db_query($sql);

} else {
	exit_error('Error','Missing Info Or Invalid State. Some form variables were missing. 
		If you are certain you entered everything, <B>PLEASE</B> report to '. $GLOBALS['sys_email_admin'].' and
		include info on your browser and platform configuration');
}

$HTML->header(array('title'=>'License'));

include(util_get_content('register/license'));

$HTML->footer(array());

?>

