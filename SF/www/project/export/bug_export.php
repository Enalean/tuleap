<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$

$Language->loadLanguageMsg('project/project');

bug_init($group_id);

$col_list = array();
$select = 'SELECT DISTINCT NULL';
$from = 'FROM bug';
$where = "WHERE bug.group_id='$group_id' ";

// Now process the variable list of fields used by this project
$is=0;
while ($field = bug_list_all_fields()) {

    // skip if field not used. Skip also if it is comment type because it actually
    // belongs to bug_history
    if (!bug_data_is_used($field) || ($field == 'comment_type_id')) {
	continue;
    }

    $col_list[] = $field;
    $lbl_list[$field] = bug_data_get_label($field);
    $dsc_list[$field] = bug_data_get_description($field);

    // user names requires some special processing 
    if (bug_data_is_username_field($field)) {
	// user names requires some special processing to display the username
	// instead of the user_id
	$select .= ",user_$field.user_name AS $field";
	$from .= ",user user_$field";
	$where .= " AND user_$field.user_id=bug.$field ";
    } else {
	// otherwise just select this column as is
	$select .= ",bug.$field";
    }
}


// Add the 3 fields that we build ourselves for user convenience
// - All follow-up comments
// - Task dependencies
// - Bug Dependencies

$col_list[] = 'follow_ups';
$col_list[] = 'is_dependent_on_task_id';
$col_list[] = 'is_dependent_on_bug_id';

$lbl_list['follow_ups'] = $Language->getText('project_export_artifact_export','follow_up_comments');
$lbl_list['is_dependent_on_task_id'] = $Language->getText('project_export_bug_export','depend_on_tasks');
$lbl_list['is_dependent_on_bug_id'] = $Language->getText('project_export_bug_export','depend_on_bugs');

$dsc_list['follow_ups'] = $Language->getText('project_export_artifact_export','all_followup_comments');
$dsc_list['is_dependent_on_task_id'] = $Language->getText('project_export_bug_export','depend_on_tasks_desc');
$dsc_list['is_dependent_on_bug_id'] = $Language->getText('project_export_bug_export','depend_on_bugs_desc');

$tbl_name = 'bug';

$eol = "\n";
    
$sql = "$select $from $where";
//echo "DBG -- $sql<br>";

$result=db_query($sql);
$rows = db_numrows($result);    

if ($export == 'bug') {

    // Send the result in CSV format
    if ($result && $rows > 0) {
	
	header ('Content-Type: text/csv');
	header ('Content-Disposition: filename=bug_'.$dbname.'.csv');

	echo build_csv_header($col_list, $lbl_list).$eol;
	
	while ($arr = db_fetch_array($result)) {	    
	    prepare_bug_record($group_id,$col_list, $arr);
	    echo build_csv_record($col_list, $arr).$eol;
	}

    } else {

	project_admin_header(array('title'=>$pg_title));

	echo '<h3>'.$Language->getText('project_export_bug_deps_export','bug_deps_export','Bug').'</h3>';
	if ($result) {
	    echo '<P>'.$Language->getText('project_export_bug_deps_export','no_bug_deps_found','bug');
	} else {
	    echo '<P>'.$Language->getText('project_export_bug_deps_export','db_access_err',array('bug',$GLOBALS['sys_name']));
	    echo '<br>'.db_error();
	}
	site_project_footer( array() );
    }


} else if ($export == "bug_format") {

  echo $Language->getText('project_export_bug_deps_export','bug_deps_export_format',' Bug');

    $record = pick_a_record_at_random($result, $rows, $col_list);
    prepare_bug_record($group_id,$col_list,$record);
    display_exported_fields($col_list,$lbl_list,$dsc_list,$record);

} else if ($export == "project_db") {


    // make sure the database name is not the same as the 
    // CodeX database name !!!!
    if ($dbname != $sys_dbname) {

	// Let's create the project database if it does not exist
	// Drop the existing table and create a fresh one
	db_project_create($dbname);
	db_project_query($dbname,'DROP TABLE IF EXISTS '.$tbl_name);
	
	reset($col_list);
	while (list(,$col) = each($col_list)) {

	    // process some special fields first. They do not follow
	    // the general pattern
	    if ($col == 'group_id' || $col == 'bug_id') {
		$type = 'INTEGER';

	    } else if ($col == 'hours') {
		$type = 'FLOAT(10,2)';

	    } else if ($col == 'is_dependent_on_task_id' ||
		       $col == 'is_dependent_on_bug_id') {
		$type = 'VARCHAR(255)';

	    } else if (bug_data_is_text_area($col) ||
		       $col == 'follow_ups') {
		$type = 'TEXT';

	    } else if (bug_data_is_date_field($col)) {
		$type = 'DATETIME';

	    } else {
		// For all other fields (select box and text fields)
		// export as VARCHAR(255) because we use their 
		// textual representation anyway
		$type = 'VARCHAR(255)';
	    }

	    $sql_create .= $col.' '.$type.',';

	} // end while

	// remove excess trailing comma and create the table
	$sql_create = substr($sql_create,0,-1);
	$sql_create = 'CREATE TABLE '.$tbl_name.' ('.$sql_create.')';
	//echo "<br>BDG - $sql_create<br>";
	$res = db_project_query($dbname, $sql_create);

	// extract data from the bug table and insert them into
	// the project database table
	if ($res) {
	    
	    while ($arr = db_fetch_array($result)) {
		prepare_bug_record($group_id,$col_list,$arr);
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
