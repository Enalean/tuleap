<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$


// This is the SQL query to retrieve all the bugs which depends on another bug

$sql = 'SELECT bug_bug_dependencies.bug_id,'.
"$group_id AS group_id,bug_bug_dependencies.is_dependent_on_bug_id ".
'FROM bug_bug_dependencies, bug '.
'WHERE bug_bug_dependencies.bug_id=bug.bug_id AND '.
"bug.group_id='$group_id' AND ".
'bug_bug_dependencies.is_dependent_on_bug_id <> 100';

$col_list = array('bug_id','group_id','is_dependent_on_bug_id');
$lbl_list = array('bug_id' => 'Bug ID',
	     'group_id' => 'Group ID',
	     'is_dependent_on_bug_id' => 'Depend on Bug');
$dsc_list = array('bug_id' => 'Unique bug identifier',
	     'group_id' => 'Unique project identifier',
	     'is_dependent_on_bug_id' => 'Depend on Bug');

$tbl_name = 'bug_bug_dependencies';

$eol = "\n";

//echo "DBG -- $sql<br>";

$result=db_query($sql);
$rows = db_numrows($result);

if ($export == 'bug_bug_deps') {

    // Send the result in CSV format
    if ($result && $rows > 0) {
	
	header ('Content-Type: text/csv');
	header ('Content-Disposition: filename='.$tbl_name.'_'.$dbname.'.csv');
	
	echo build_csv_header($col_list, $lbl_list).$eol;

	while ($arr = db_fetch_array($result)) {
	    echo build_csv_record($col_list, $arr).$eol;
	}

    } else {

	project_admin_header(array('title'=>$pg_title));

	echo '<h3>Bug-Bug Dependencies Export</h3>';
	if ($result) {
	    echo '<P>No bug-bug depencies  found. Could not generate an export.';
	} else {
	    echo '<P>Error while accessing your bug dependencies database. Please report the error to the '.$GLOBALS['sys_name'].' Administrator';
	    echo '<br>'.db_error();
	}
	site_project_footer( array() );
    }


} else if ($export == "bug_bug_deps_format") {

    echo '<h3>Bug-Bug Dependencies Export Format</h3> The Bug-Bug
Dependencies export provides you with the following fields. The sample
values indicate what the field data types are. <p>';
 
    $record = pick_a_record_at_random($result, $rows, $col_list);

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
	    'bug_id INTEGER, group_id INTEGER, is_dependent_on_bug_id INTEGER)';

	$res = db_project_query($dbname, $sql_create);

	// extract data from the bug table and insert them into
	// the project database table. Do it in one shot here.
	if ($res) {	    
	    while ($arr = db_fetch_array($result)) {
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
