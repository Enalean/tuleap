<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');

$sql="SELECT * FROM snippet_version WHERE snippet_version_id='$id'";
$result=db_query($sql);

if ($result && db_numrows($result) > 0) {

   if (db_result($result,0,'filesize') == 0) {

	// if snippet was just copy-pasted then show it directly
	header('Content-Type: text/plain');
	if (strlen(db_result($result,0,'code')) > 1) {
		echo util_unconvert_htmlspecialchars( db_result($result,0,'code') );
	} else {
		echo 'nothing in here';
	}

   } else {
	
	// Download the patch with the correct filetype
	header('Content-Type: '.db_result($result,0,'filetype'));
	header('Content-Length: '.db_result($result,0,'filesize'));
	header('Content-Disposition: filename='.db_result($result,0,'filename'));

	echo db_result($result,0,'code');

    }

} else {
	echo 'Error';
}

?>
