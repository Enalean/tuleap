<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$

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

    // user names requires some special processing and keep priority
    //  as an integer value
    if ($field == 'assigned_to') {
	$select .= ',user_at.user_name AS assigned_to';
	$from .= ',user user_at';
	$where .= ' AND user_at.user_id=bug.assigned_to ';
    } else if  ($field == 'submitted_by') {
	$select .= ',user.user_name AS submitted_by';
	$from .= ',user';
	$where .= ' AND user.user_id=bug.submitted_by ';
    } else {

	if (bug_data_is_select_box($field) && ($field != 'priority') ) {
	    // we need to "decode" the value_id and return the corresponding
	    // user readable value. Use field_id instead of field_name to speed
	    // up query process
	    $bfv_alias = 'bug_field_value'."$is";
	    $select .= ",$bfv_alias.value AS $field";
	    $from .= ",bug_field_value $bfv_alias";
	    $where .= " AND ($bfv_alias.bug_field_id=".bug_data_get_field_id($field).
		" AND $bfv_alias.value_id=bug.$field AND ($bfv_alias.group_id='$group_id' OR $bfv_alias.group_id='100')) ";
	    $is++;
	} else {
	    // It's a text field so leave it as it is
	    $select .= ",bug.$field";
	}
    }
}

// Add the 3 fields that we build ourselves for user convenience
// - All follow-up comments
// - Task dependencies
// - Bug Dependencies

$col_list[] = 'follow_ups';
$col_list[] = 'is_dependent_on_task_id';
$col_list[] = 'is_dependent_on_bug_id';

$lbl_list['follow_ups'] = 'Follow-up Comments';
$lbl_list['is_dependent_on_task_id'] = 'Depend on Task(s)';
$lbl_list['is_dependent_on_bug_id'] = 'Depend on Bug(s)';

$dsc_list['follow_ups'] = 'All follow-up comments in one chunck of text';
$dsc_list['is_dependent_on_task_id'] = 'List of  tasks this bug depends on';
$dsc_list['is_dependent_on_bug_id'] = 'List of  bugs this bug depends on';

$tbl_name = 'bug';

// Determine which end of line sequence to use in the CSV output
// depending on the browser platform
$eol = (browser_is_windows() ? "\r\n":"\n");
    
$sql = "$select $from $where";
//    echo "DBG -- $sql<br>;

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

	echo '<h3>Bug export</h3>';
	if ($result) {
	    echo '<P>No bug found. Could not generate an export.';
	} else {
	    echo '<P>Error while accessing your bug database. Please report the error to the CodeX administrator';
	    echo '<br>'.db_error();
	}
	site_project_footer( array() );
    }


} else if ($export == "bug_format") {

    echo '<h3>Bug Export Format</h3>
The Bug export provides you with the following bug fields. The sample values indicate what the field data types are.<p>';

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

	    // all fields are created with type varchar(255) except
	    // for some exceptions. This is really quick and dirty and 
	    // column type should be determined from both the data
	    // type of the columns in the bug table and the display type
	    // from the bug_field table
	    switch ($col) {
	    case 'date':
	    case 'close_date':
		$type = 'DATETIME';
		break;
		
	    case 'group_id':
	    case 'bug_id':
		$type = 'INTEGER';
		break;
		
	    case 'hours':
		$type = 'FLOAT(10,2)';
		break;

	    case 'summary':
		$type = 'TEXT';
		break;

	    case 'follow_ups':
		$type = 'TEXT';
		break;

	    case 'is_dependent_on_task_id':
	    case 'is_dependent_on_bug_id':
		$type = 'VARCHAR(255)';
		break;

	    default:
		$type = 'VARCHAR(255)';
		break;
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
	    $feedback .= 'Error in Create project '.$tbl_name.' table:'.db_project_error();
	}

    } else {
	$feedback .= "SECURITY VIOLATION!!! Unauthorized database name: $dbname";
    }
   
}

?>
