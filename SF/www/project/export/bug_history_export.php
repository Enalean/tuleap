<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$

$Language->loadLanguageMsg('project/project');

bug_init($group_id);

// This is the SQL query to retrieve all the bug history for this group
// Note: the text value of the comment_type_id is not fetched by the
// SQL statement because this field can be NULL and the NULL value is
// not a valid comment type in the bug_field_value table. So the join
// doesn't work.
$sql = "SELECT bug_history.bug_id,'$group_id' AS group_id,bug_history.field_name,".
'bug_history.old_value, user.user_name AS mod_by, bug_history.date, bug_history.type'.
' FROM bug,bug_history, user '.
"WHERE (bug_history.bug_id=bug.bug_id AND bug.group_id='$group_id' )".
'AND user.user_id=bug_history.mod_by';

$col_list = array('bug_id','group_id','field_name','old_value','mod_by','date','type');
$lbl_list = array('bug_id' => '',
		  'group_id' => '',
		  'field_name' => $Language->getText('project_export_artifact_history_export','field_name'),
		  'old_value' => $Language->getText('project_export_artifact_history_export','old_val'),
		  'mod_by' => $Language->getText('project_export_artifact_history_export','mod_by'),
		  'date' => $Language->getText('project_export_artifact_history_export','mod_on'),
		  'type' => $Language->getText('project_export_artifact_history_export','comment_type'));
$lbl_list['bug_id'] = bug_data_get_label('bug_id');
$lbl_list['group_id'] = bug_data_get_label('group_id');

$dsc_list = array('bug_id' => '',
		  'group_id' => '',
		  'field_name' => $Language->getText('project_export_artifact_history_export','field_name_desc'),
		  'old_value' => $Language->getText('project_export_artifact_history_export','old_val_desc'),
		  'mod_by' => $Language->getText('project_export_artifact_history_export','mod_by_desc'),
		  'date' => $Language->getText('project_export_artifact_history_export','mod_by_desc'),
		  'type' => $Language->getText('project_export_artifact_history_export','comment_type_desc'));

$dsc_list['bug_id'] = bug_data_get_description('bug_id');
$dsc_list['group_id'] = bug_data_get_description('group_id');

$tbl_name = 'bug_history';

$eol = "\n";

$result=db_query($sql);
$rows = db_numrows($result);       

if ($export == 'bug_history') {

    // Send the result in CSV format
    if ($result && $rows > 0) {
	
	header ('Content-Type: text/csv');
	header ('Content-Disposition: filename=bug_history_'.$dbname.'.csv');
	
	echo build_csv_header($col_list, $lbl_list).$eol;

	while ($arr = db_fetch_array($result)) {
	    prepare_bug_history_record($group_id,$col_list,$arr);
	    echo build_csv_record($col_list, $arr).$eol;
	}

    } else {

	project_admin_header(array('title'=>$pg_title));

	echo '<h3>'.$Language->getText('project_export_bug_deps_export','bug_deps_export','Bug History').'</h3>';
	if ($result) {
	    echo '<P>'.$Language->getText('project_export_bug_deps_export','no_bug_deps_found','bug history');
	} else {
	    echo '<P>'.$Language->getText('project_export_bug_deps_export','db_access_err',array('bug history',$GLOBALS['sys_name']));
	    echo '<br>'.db_error();
	}
	site_project_footer( array() );
    }


} else if ($export == "bug_history_format") {

    echo $Language->getText('project_export_bug_deps_export','bug_deps_export_format',' Bug History');
   
    $record = pick_a_record_at_random($result, $rows, $col_list);
    prepare_bug_history_record($group_id,$col_list,$record);   
    display_exported_fields($col_list,$lbl_list,$dsc_list,$record);


} else if ($export == "project_db") {

    // make sure the database name is not the same as the 
    // CodeX database name !!!!
    if ($dbname != $sys_dbname) {

	// Let's create the project database if it does not exist
	// Drop the existing table and create a fresh one
	db_project_create($dbname);
	db_project_query($dbname,'DROP TABLE IF EXISTS '. $tbl_name);
	
	$sql_create = "CREATE TABLE $tbl_name (".
	    'bug_id INTEGER, group_id INTEGER, field_name VARCHAR(255), '.
	    'old_value TEXT, mod_by VARCHAR(255), date DATETIME, '.
	    'type VARCHAR(255))';

	$res = db_project_query($dbname, $sql_create);

	// extract data from the bug table and insert them into
	// the project database table
	if ($res) {
	    while ($arr = db_fetch_array($result)) {
		prepare_bug_history_record($group_id,$col_list,$arr);
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
