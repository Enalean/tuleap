<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    // Initial db and session library, opens session
require($DOCUMENT_ROOT.'/include/vars.php');
session_require(array('isloggedin'=>'1'));
require($DOCUMENT_ROOT.'/include/account.php');

$Language->loadLanguageMsg('register/register');

if ($group_id && $insert_license && $rand_hash && $form_license) {
	/*
		Hash prevents them from updating a live, existing group account
	*/
	$sql="UPDATE groups SET license='$form_license', license_other='$form_license_other' ".
		"WHERE group_id='$group_id' AND rand_hash='__$rand_hash'";
	$result=db_query($sql);
	if (db_affected_rows($result) < 1) {
		exit_error($Language->getText('global','error'),$Language->getText('register_category','upd_query_fail',$GLOBALS['sys_email_admin']));
	}

} else {
	exit_error($Language->getText('global','error'),$Language->getText('register_category','var_missing',$GLOBALS['sys_email_admin']));
}

$HTML->header(array('title'=>$Language->getText('register_category','project_category')));

include(util_get_content('register/category'));

$HTML->footer(array());

?>

