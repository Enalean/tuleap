<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    // Initial db and session library, opens session
session_require(array('isloggedin'=>'1'));
require_once('account.php');

$Language->loadLanguageMsg('register/register');

// push received vars
if ($insert_purpose && $form_purpose && $form_short_description && $built_from_template) { 

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
                . "required_software,patents_ips,other_comments,register_time,license_other,rand_hash,built_from_template) VALUES ("
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
		. "'__$random_num','__".md5($random_num)."',"
		. "'".$built_from_template."')");

	if (!$result) {
		exit_error($Language->getText('global','error'),$Language->getText('register_projectname','ins_query_fail',$GLOBALS['sys_email_admin']));
	} else {
		$group_id=db_insertid($result);

		// insert trove categories from template project
		$db_res = db_query("SELECT trove_cat_id,trove_cat_version,'
		.'group_id,trove_cat_root FROM trove_group_link WHERE group_id = '$built_from_template'");

		while ($row = db_fetch_array($db_res)) {
		  db_query("INSERT INTO trove_group_link (trove_cat_id,trove_cat_version,"
			   ."group_id,trove_cat_root) VALUES (".$row['trove_cat_id'].",".time().",$group_id,".$row['trove_cat_root'].")");
		}
	}

} else {
	exit_error($Language->getText('global','error'),$Language->getText('register_projectname','info_missed'));
}

$HTML->header(array('title'=>$Language->getText('register_form','project_name')));

include($Language->getContent('register/projectname'));

$HTML->footer(array());

?>

