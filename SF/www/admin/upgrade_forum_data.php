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

	calculates and updates the has_followups flag in the forums

*/

require ('pre.php');    

session_require(array('group'=>'1','admin_flags'=>'A'));

//get all the tasks
$result=db_query("SELECT msg_id FROM forum ORDER BY msg_id ASC LIMIT $z,10000");
$rows=db_numrows($result);
echo db_error();
echo "\nRows: $rows\n";
flush();

for ($i=0; $i<$rows; $i++) {

	//echo "\n".db_result($result,$i,'msg_id')."\n";

	/*
		//insert a default bug dependency
	*/

	$res2=db_query("SELECT count(*) FROM forum WHERE is_followup_to='". db_result($result,$i,'msg_id') ."'");
	$rows2=db_numrows($res2);
	if (db_result($res2,0,0) < 1) {
		// don't do anything
		$counter2++;
		//echo db_error();
	} else {
		$counter++;
		db_query ("UPDATE forum SET has_followups='1' WHERE msg_id='". db_result($result,$i,'msg_id') ."'");
	}
}

echo "\n\nUpdated: $counter";
echo "\n\nSkipped: $counter2";
flush();

?>
