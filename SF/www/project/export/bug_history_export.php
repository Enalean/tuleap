<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$

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
		  'field_name' => 'Field Name',
		  'old_value' => 'Old Value',
		  'mod_by' => 'Modified By',
		  'date' => 'Modified On',
		  'type' => 'Comment Type');
$lbl_list['bug_id'] = bug_data_get_label('bug_id');
$lbl_list['group_id'] = bug_data_get_label('group_id');

$dsc_list = array('bug_id' => '',
		  'group_id' => '',
		  'field_name' => 'Name of the bug field which value changed',
		  'old_value' => 'Value of the bug field before it changed',
		  'mod_by' => 'Login name of the user who changed the value',
		  'date' => 'Modification date',
		  'type' => 'Type of the followup comment added to history (this field only applies for field name \'<tt>details</tt>\'');

$dsc_list['bug_id'] = bug_data_get_description('bug_id');
$dsc_list['group_id'] = bug_data_get_description('group_id');

$tbl_name = 'bug_history';

$eol = "\n";

//echo "DBG -- $sql<br>";

$result=db_query($sql);
$rows = db_numrows($result);       

if ($export == 'bug_history') {

    // Send the result in CSV format
    if ($result && $rows > 0) {
	
	header ('Content-Type: text/csv');
	header ('Content-Disposition: filename=bug_history_'.$dbname.'.csv');
	
	echo build_csv_header($col_list, $lbl_list).$eol;

	while ($arr = db_fetch_array($result)) {
	    prepare_bug_history_record($arr);
	    echo build_csv_record($col_list, $arr).$eol;
	}

    } else {

	project_admin_header(array('title'=>$pg_title));

	echo '<h3>Bug History export</h3>';
	if ($result) {
	    echo '<P>No bug history found. Could not generate an export.';
	} else {
	    echo '<P>Error while accessing your bug history database. Please report the error to the CodeX administrator';
	    echo '<br>'.db_error();
	}
	site_project_footer( array() );
    }


} else if ($export == "bug_history_format") {

    echo '<h3>Bug History Export Format</h3> The Bug History export
provides you with the following bug history fields. The sample values
indicate what the field data types are.<p>';
   
    $record = pick_a_record_at_random($result, $rows, $col_list);
    prepare_bug_history_record($record);   
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
		prepare_bug_history_record($arr);
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
