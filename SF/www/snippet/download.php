<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');

$sql="SELECT * FROM snippet_version WHERE snippet_version_id='$id'";
$result=db_query($sql);

if ($result && db_numrows($result) > 0) {
	header('Content-Type: text/plain');
	if (strlen(db_result($result,0,'code')) > 1) {
		echo util_unconvert_htmlspecialchars( db_result($result,0,'code') );
	} else {
		echo 'nothing in here';
	}
} else {
	echo 'Error';
}

?>
