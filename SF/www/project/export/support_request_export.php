<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$


// This is the SQL query to retrieve all the task history for this group

$sql = "SELECT support.support_id,'$group_id' AS group_id,".
'support_category.category_name AS support_category, support.summary,'.
'support.priority, user.user_name AS submitted_by, user2.user_name AS assigned_to,'.
'support.open_date, support.close_date, support_status.status_name AS status '.
'FROM support, support_category,support_status, user, user user2 '.
"WHERE (support.group_id='$group_id' AND ".
'support.support_category_id=support_category.support_category_id  AND '.
'support.support_status_id=support_status.support_status_id AND '.
'user.user_id=support.submitted_by AND user2.user_id=support.assigned_to)';

$col_list = array('support_id','group_id','support_category','summary',
		  'priority','submitted_by','assigned_to', 'open_date','close_date',
		  'status','follow_ups');
$lbl_list = array('support_id' => 'Support ID',
		  'group_id' => 'Group ID',
		  'support_category' => 'Category',
		  'summary' => 'Summary',
		  'priority' => 'Priority',
		  'submitted_by' => 'Submitted by',
		  'assigned_to' => 'Assigned to',
		  'open_date' => 'Open Date',
		  'close_date' => 'Close Date',
		  'status' => 'Status',
		  'follow_ups' => 'Follow-up Comments');
$dsc_list = array('support_id' => 'Unique support request identifier',
		  'group_id' => 'Unique project identifier',
		  'support_category' => 'Name of the category the support request is in',
		  'summary' => 'One line description of the support request',
		  'priority' => 'Priority',
		  'submitted_by' => 'Name of the user who submitted the support request',
		  'assigned_to' => 'Project member the support request is assigned to',
		  'open_date' => 'Support request submission date',
		  'close_date' => 'Support request close date',
		  'status' => 'Status (Open, Closed,...)',
		  'follow_ups' => 'All follow-up comments in one chunck of text including the original description');

$tbl_name = 'support_request';

$eol = "\n";

$result=db_query($sql);
$rows = db_numrows($result);


if ($export == 'support_request') {

    // Send the result in CSV format
    if ($result && $rows > 0) {
	
	header ('Content-Type: text/csv');
	header ('Content-Disposition: filename=support_request_'.$dbname.'.csv');
	
	echo build_csv_header($col_list, $lbl_list).$eol;

	while ($arr = db_fetch_array($result)) {
	    prepare_support_request_record($group_id,$arr);
	    echo build_csv_record($col_list, $arr).$eol;
	}

    } else {

	project_admin_header(array('title'=>$pg_title));

	echo '<h3>Support Request Export</h3>';
	if ($result) {
	    echo '<P>No support request found. Could not generate an export.';
	} else {
	    echo '<P>Error while accessing your support request database. Please report the error to the '.$GLOBALS['sys_name'].' Administrator';
	    echo '<br>'.db_error();
	}
	site_project_footer( array() );
    }


} else if ($export == "support_request_format") {

    echo '<h3>Support Request Export Format</h3> The Support Request export provides you
with the following suppor request fields. The sample values indicate what the
field data types are. <p>';

    $record = pick_a_record_at_random($result, $rows, $col_list);
    prepare_support_request_record($group_id,$record);   
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
	    'support_id INTEGER, group_id INTEGER, support_category VARCHAR(255), '.
	    'summary VARCHAR(255), priority INTEGER, '.
	    'submitted_by VARCHAR(255), assigned_to VARCHAR(255), '.
	    'open_date DATETIME, close_date DATETIME, '.
	    'status VARCHAR(255), follow_ups TEXT)';

	$res = db_project_query($dbname, $sql_create);

	// extract data from the support requests table and insert them into
	// the project database table
	if ($res) {
	    
	    while ($arr = db_fetch_array($result)) {
		prepare_support_request_record($group_id,$arr);
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
