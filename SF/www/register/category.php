<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    // Initial db and session library, opens session
require "vars.php";
session_require(array('isloggedin'=>'1'));
require "account.php";

if ($group_id && $insert_license && $rand_hash && $form_license) {
	/*
		Hash prevents them from updating a live, existing group account
	*/
	$sql="UPDATE groups SET license='$form_license', license_other='$form_license_other' ".
		"WHERE group_id='$group_id' AND rand_hash='__$rand_hash'";
	$result=db_query($sql);
	if (db_affected_rows($result) < 1) {
		exit_error('Error','This is an invalid state. Update query failed. <B>PLEASE</B> report to '.$GLOBALS['sys_email_admin']);
	}

} else {
	exit_error('Error','This is an invalid state. Some form variables were missing.
		If you are certain you entered everything, <B>PLEASE</B> report to '.$GLOBALS['sys_email_admin'].' and
		include info on your browser and platform configuration');
}

$HTML->header(array('title'=>'Project Category'));

util_get_content('register/category');

$HTML->footer(array());

?>

