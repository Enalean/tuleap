<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require 'pre.php';    // Initial db and session library, opens session
session_require(array('isloggedin'=>'1'));
require 'vars.php';
require('../forum/forum_utils.php');

if ($show_confirm) {

	$HTML->header(array('title'=>'Registration Complete'));

    util_get_content('register/confirmation');

	$HTML->footer(array());

} else if ($i_agree && $group_id && $rand_hash) {
	/*

		Finalize the db entries

	*/

	$result=db_query("UPDATE groups SET status='P', ".
		"register_purpose='".htmlspecialchars($form_purpose)."', ".
		"required_software='".htmlspecialchars($form_required_sw)."', ".
		"patents_ips='".htmlspecialchars($form_patents)."', ".
		"other_comments='".htmlspecialchars($form_comments)."', ".
		"group_name='$form_full_name', license='$form_license', ".
		"license_other='".htmlspecialchars($form_license_other)."' ".
		"WHERE group_id='$group_id' AND rand_hash='__$rand_hash'");

	if (db_affected_rows($result) < 1) {
		exit_error('Error','UDPATING TO ACTIVE FAILED. <B>PLEASE</B> report to '.$GLOBALS['sys_email_admin'].' '.db_error());
	}

	// define a module
	$result=db_query("INSERT INTO filemodule (group_id,module_name) VALUES ('$group_id','".group_getunixname($group_id)."')");
	if (!$result) {
		exit_error('Error','INSERTING FILEMODULE FAILED. <B>PLEASE</B> report to admin@'.$GLOBALS['sys_default_domain'].' '.db_error());
	}

	// make the current user an admin
	$result=db_query("INSERT INTO user_group (user_id,group_id,admin_flags,bug_flags,forum_flags) VALUES ("
		. user_getid() . ","
		. $group_id . ","
		. "'A'," // admin flags
		. "2," // bug flags
		. "2)"); // forum_flags	
	if (!$result) {
		exit_error('Error','SETTING YOU AS OWNER FAILED. <B>PLEASE</B> report to '.$GLOBALS['sys_email_admin'].' '.db_error());
	}

	//Add a couple of forums for this group and make the project creator 
	// (current user) monitor these forums
	$fid = forum_create_forum($group_id,'Open Discussion',1,'General Discussion');
	forum_add_monitor($fid, user_getid());

	$fid = forum_create_forum($group_id,'Help',1,'Get Help');
	forum_add_monitor($fid, user_getid());
	$fid = forum_create_forum($group_id,'Developers',0,'Project Developer Discussion');
	forum_add_monitor($fid, user_getid());

	//Set up some mailing lists
	//will be done at some point. needs to communicate with geocrawler

	//
	$HTML->header(array('title'=>'Registration Complete'));
	
    util_get_content('register/complete');
    
	$HTML->footer(array());

} else if ($i_disagree && $group_id && $rand_hash) {

	$HTML->header(array('title'=>'Registration Deleted'));
	$result=db_query("DELETE FROM groups ".
		"WHERE group_id='$group_id' AND rand_hash='__$rand_hash'");

	echo '
		<H2>Project Deleted</H2>
		<P>
		<B>Please try again in the future.</B>';
	$HTML->footer(array());

} else {
	exit_error('Error','This is an invalid state. Some form variables were missing.
		If you are certain you entered everything, <B>PLEASE</B> report to '.$GLOBALS['sys_email_admin'].' and
		include info on your browser and platform configuration');

}

?>

