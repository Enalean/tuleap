<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

exit;

/*

	One-time use script

*/

require ('pre.php');    

session_require(array('group'=>'1','admin_flags'=>'A'));

//get all the tasks
$result=db_query("SELECT project_task_id FROM project_task ORDER BY project_task_id ASC");
$rows=db_numrows($result);
echo "\nRows: $rows\n";
flush();

for ($i=0; $i<$rows; $i++) {

	echo "\n".db_result($result,$i,'project_task_id')."\n";

	/*
		//insert a default dependency
	*/

	$res2=db_query("SELECT * FROM project_dependencies WHERE project_task_id='". db_result($result,$i,'project_task_id') ."'");
	$rows2=db_numrows($res2);
	if ($rows2 < 1) {
		db_query ("INSERT INTO project_dependencies VALUES ('','". db_result($result,$i,'project_task_id') ."','100')");
	} else if ($rows2 > 1) {
		db_query ("DELETE FROM project_dependencies WHERE project_task_id='". db_result($result,$i,'project_task_id') ."' AND is_dependent_on_task_id='100'");
	}

	/*
		//insert a default assignee 
	*/

	$res2=db_query("SELECT * FROM project_assigned_to WHERE project_task_id='". db_result($result,$i,'project_task_id') ."'");
	$rows2=db_numrows($res2);
	if ($rows2 < 1) {
		db_query ("INSERT INTO project_assigned_to VALUES ('','". db_result($result,$i,'project_task_id') ."','100')");
	} else if ($rows2 > 1) {
		db_query ("DELETE FROM project_assigned_to WHERE project_task_id='". db_result($result,$i,'project_task_id') ."' AND assigned_to_id='100'");
	}

}

?>
