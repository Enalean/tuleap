<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

exit;

/*

	One time use script

*/

require ('pre.php');    

session_require(array('group'=>'1','admin_flags'=>'A'));

//get all the tasks
$result=db_query("SELECT bug_id FROM bug ORDER BY bug_id ASC");
$rows=db_numrows($result);
echo "\nRows: $rows\n";
flush();

for ($i=0; $i<$rows; $i++) {

	echo "\n".db_result($result,$i,'bug_id')."\n";

	/*
		//insert a default bug dependency
	*/

	$res2=db_query("SELECT * FROM bug_bug_dependencies WHERE bug_id='". db_result($result,$i,'bug_id') ."'");
	$rows2=db_numrows($res2);
	if ($rows2 < 1) {
		db_query ("INSERT INTO bug_bug_dependencies VALUES ('','". db_result($result,$i,'bug_id') ."','100')");
	} else if ($rows2 > 1) {
		db_query ("DELETE FROM bug_bug_dependencies WHERE bug_id='". db_result($result,$i,'bug_id') ."' AND is_dependent_on_bug_id='100'");
	}

	/*
		//insert a default task dependency
	*/

	$res2=db_query("SELECT * FROM bug_task_dependencies WHERE bug_id='". db_result($result,$i,'bug_id') ."'");
	$rows2=db_numrows($res2);
	if ($rows2 < 1) {
		db_query ("INSERT INTO bug_task_dependencies VALUES ('','". db_result($result,$i,'bug_id') ."','100')");
	} else if ($rows2 > 1) {
		db_query ("DELETE FROM bug_task_dependencies WHERE bug_id='". db_result($result,$i,'bug_id') ."' AND is_dependent_on_task_id='100'");
	}

}

?>
