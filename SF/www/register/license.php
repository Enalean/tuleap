<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    // Initial db and session library, opens session
require($DOCUMENT_ROOT.'/include/vars.php');
require($DOCUMENT_ROOT.'/include/account.php');

$Language->loadLanguageMsg('register/register');

session_require(array('isloggedin'=>'1'));

if ($insert_group_name && $group_id && $rand_hash && $form_full_name && $form_unix_name) {
	/*
		check for valid group name
	*/
	if (!account_groupnamevalid($form_unix_name)) {
		exit_error($Language->getText('register_license','invalid_g_name'),$register_error);
	}
	/*
		See if it's taken already
	*/
	if (db_numrows(db_query("SELECT group_id FROM groups WHERE unix_group_name LIKE '$form_unix_name'")) > 0) {
		exit_error($Language->getText('register_license','g_name_taken'),$Language->getText('register_license','g_name_exist'));
	}
	/*
		Hash prevents them from updating a live, existing group account
	*/
	$sql="UPDATE groups SET unix_group_name='". strtolower($form_unix_name) ."', group_name='$form_full_name', ".
		"http_domain='$form_unix_name.$GLOBALS[sys_default_domain]' ".
		"WHERE group_id='$group_id' AND rand_hash='__$rand_hash'";
	$result=db_query($sql);

} else {
	exit_error($Language->getText('global','error'),$Language->getText('register_category','var_missing',$GLOBALS['sys_email_admin']));
}

$HTML->header(array('title'=>$Language->getText('register_license','license')));

include(util_get_content('register/license'));

$HTML->footer(array());

?>

