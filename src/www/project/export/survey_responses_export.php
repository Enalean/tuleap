<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id: survey_responses_export.php 4419 2006-12-06 14:39:15 +0000 (Wed, 06 Dec 2006) mnazaria $

$Language->loadLanguageMsg('project/project');

// This is the SQL query to retrieve all the survey responses for this group

$sql = "SELECT group_id,survey_id,user_id,question_id,response,date,date AS date_stamp ".
'FROM survey_responses '.
"WHERE group_id='$group_id' ".
'ORDER BY survey_id, date_stamp, question_id';

$col_list = array('group_id','survey_id','user_id','question_id',
		  'response','date','date_stamp');
$lbl_list = array( 'group_id' => $Language->getText('project_export_bug_deps_export','g_id'),
		   'survey_id' => $Language->getText('project_export_survey_responses_export','survey_id'),
		   'user_id' => $Language->getText('project_export_survey_responses_export','user_id'),
		   'question_id' => $Language->getText('project_export_survey_responses_export','question_id'),
		   'response' => $Language->getText('project_export_survey_responses_export','response'),
		   'date' => $Language->getText('project_admin_utils','date'),
		  'date_stamp' => $Language->getText('project_export_survey_responses_export','date_stamp'));
$dsc_list = array( 'group_id' => $Language->getText('project_export_bug_deps_export','g_id_desc'),
		   'survey_id' => $Language->getText('project_export_survey_responses_export','survey_id_desc'),
		   'user_id' => $Language->getText('project_export_survey_responses_export','user_id_desc'),
		   'question_id' => $Language->getText('project_export_survey_responses_export','question_id_desc'),
		   'response' => $Language->getText('project_export_survey_responses_export','response_desc'),
		   'date' => $Language->getText('project_export_survey_responses_export','date_desc'),
		   'date_stamp' => $Language->getText('project_export_survey_responses_export','date_stamp_desc'));

$tbl_name = 'survey_responses';

$eol = "\n";

//echo "DBG -- $sql<br>";

$result=db_query($sql);
$rows = db_numrows($result);


if ($export == 'survey_responses') {

    // Send the result in CSV format
    if ($result && $rows > 0) {
	
	header ('Content-Type: text/csv');
	header ('Content-Disposition: filename=survey_responses_'.$dbname.'.csv');
	
	echo build_csv_header($col_list, $lbl_list).$eol;

	$salt = project_export_makesalt();
	while ($arr = db_fetch_array($result)) {    
	  prepare_survey_responses_record($group_id,$arr,$salt);
	    echo build_csv_record($col_list, $arr).$eol;
	}

    } else {

	project_admin_header(array('title'=>$pg_title));

	echo '<h3>'.$Language->getText('project_export_bug_deps_export','bug_deps_export', array($Language->getText('project_export_index', 'survey_responses'))).'</h3>';
	if ($result) {
	    echo '<P>'.$Language->getText('project_export_bug_deps_export','no_bug_deps_found', array($Language->getText('project_export_index', 'survey_responses')));
	} else {
	    echo '<P>'.$Language->getText('project_export_bug_deps_export','db_access_err',array($Language->getText('project_export_index', 'survey_responses'),$GLOBALS['sys_name']));
	    echo '<br>'.db_error();
	}
	site_project_footer( array() );
    }


} else if ($export == "survey_responses_format") {

    echo $Language->getText('project_export_bug_deps_export','bug_deps_export_format',array($Language->getText('project_export_index', 'survey_responses')));

    $record = pick_a_record_at_random($result, $rows, $col_list);
    $salt = project_export_makesalt();
    prepare_survey_responses_record($group_id,$record,$salt);   
    display_exported_fields($col_list,$lbl_list,$dsc_list,$record);


} else if ($export == "project_db") {

    // make sure the database name is not the same as the 
    // CodeX database name !!!!
    if ($dbname != $sys_dbname) {

	// Let's create the project database if it does not exist
	// Drop the existing table and create a fresh one
	db_project_create($dbname);
	db_project_query($dbname,'DROP TABLE IF EXISTS '.$tbl_name);
	
	$sql_create = "CREATE TABLE $tbl_name (".
	    'group_id INTEGER, survey_id INTEGER, user_id DOUBLE, question_id INTEGER, '.
	    'response TEXT, date DATETIME, date_stamp INTEGER)';

	$res = db_project_query($dbname, $sql_create);

	// extract data from the survey table and insert them into
	// the project database table
	if ($res) {
        $salt = project_export_makesalt();
        while ($arr = db_fetch_array($result)) {
            prepare_survey_responses_record($group_id,$arr,$salt);
            insert_record_in_table($dbname, $tbl_name, $col_list, $arr);
	    }

	} else {
	    $feedback .= $Language->getText('project_export_artifact_deps_export','create_proj_err',array($tbl_name,db_project_error()));
	}

    } else {
	$feedback .= $Language->getText('project_export_artifact_deps_export','security_violation',$dbname);
    }
   
}

?>
