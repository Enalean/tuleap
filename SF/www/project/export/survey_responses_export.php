<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$


// This is the SQL query to retrieve all the survey responses for this group

$sql = "SELECT group_id,survey_id,question_id,response,date,date AS date_stamp ".
'FROM survey_responses '.
"WHERE group_id='$group_id'";

$col_list = array('group_id','survey_id','question_id',
		  'response','date','date_stamp');
$lbl_list = array( 'group_id' => 'Group ID',
		   'survey_id' => 'Survey ID',
		   'question_id' => 'Question ID',
		   'response' => 'Response',
		   'date' => 'Date',
		  'date_stamp' => 'Date Stamp');
$dsc_list = array( 'group_id' => 'Unique project identifier',
		   'survey_id' => 'Unique survey identifier',
		   'question_id' => 'Unique question identifier',
		   'response' => 'The response to the question',
		   'date' => 'Date/Time the user response was registered',
		   'date_stamp' => 'A unique identifier for the responses that belong to a survey taken by a given user');

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

	while ($arr = db_fetch_array($result)) {
	    prepare_survey_responses_record($group_id,$arr);
	    echo build_csv_record($col_list, $arr).$eol;
	}

    } else {

	project_admin_header(array('title'=>$pg_title));

	echo '<h3>Survey Responses Export</h3>';
	if ($result) {
	    echo '<P>No survey responses found. Could not generate an export.';
	} else {
	    echo '<P>Error while accessing your response database. Please report the error to the '.$GLOBALS['sys_name'].' Administrator';
	    echo '<br>'.db_error();
	}
	site_project_footer( array() );
    }


} else if ($export == "survey_responses_format") {

    echo '<h3>Survey Export Format</h3> The Survey export provides you
with the following survey fields. The sample values indicate what the
field data types are. <p>';

    $record = pick_a_record_at_random($result, $rows, $col_list);
    prepare_survey_responses_record($group_id,$record);   
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
	    'group_id INTEGER, survey_id INTEGER, question_id INTEGER, '.
	    'response TEXT, date DATETIME, date_stamp INTEGER)';

	$res = db_project_query($dbname, $sql_create);

	// extract data from the survey table and insert them into
	// the project database table
	if ($res) {
	    
	    while ($arr = db_fetch_array($result)) {
		prepare_survey_responses_record($group_id,$arr);
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
