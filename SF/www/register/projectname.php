<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    // Initial db and session library, opens session
session_require(array('isloggedin'=>'1'));
require($DOCUMENT_ROOT.'/include/account.php');

$LANG->loadLanguageMsg('register/register');

// push received vars
if ($insert_purpose && $form_purpose && $form_short_description) { 

	srand((double)microtime()*1000000);
	$random_num=rand(0,1000000);

	// Make sure default project privacy status is defined. If not
	// then default to "public"
	if (!isset($sys_is_project_public)) {
	    $sys_is_project_public = 1;
	}

	// make group entry
	$result = db_query("INSERT INTO groups (group_name,is_public,unix_group_name,http_domain,status,"
		. "unix_box,cvs_box,license,short_description,register_purpose,"
                . "required_software,patents_ips,other_comments,register_time,license_other,rand_hash) VALUES ("
		. "'__$random_num',"
		. "$sys_is_project_public," // privacy 
		. "'__$random_num',"
		. "'__$random_num',"
		. "'I'," // status
		. "'shell1'," // unix_box
		. "'cvs1'," // cvs_box
		. "'__$random_num',"
		. "'".$form_short_description."',"
		. "'".htmlspecialchars($form_purpose)."',"
		. "'".htmlspecialchars($form_required_sw)."',"
		. "'".htmlspecialchars($form_patents)."',"
		. "'".htmlspecialchars($form_comments)."',"
		. time() . ","
		. "'__$random_num','__".md5($random_num)."')");

	if (!$result) {
		exit_error($LANG->getText('global','error'),$LANG->getText('register_projectname','ins_query_fail',$GLOBALS['sys_email_admin']));
	} else {
		$group_id=db_insertid($result);
	}

} else {
	exit_error($LANG->getText('global','error'),$LANG->getText('register_projectname','info_missed'));
}

$HTML->header(array('title'=>$LANG->getText('register_form','project_name')));

include(util_get_content('register/projectname'));

$HTML->footer(array());

?>

