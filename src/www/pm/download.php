<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
//	Originally written by Stéphane Bouhet, 2002, CodeX Team, Xerox
//

require_once('pre.php');

$sql="SELECT description,file,filename,filesize,filetype FROM project_file WHERE project_file_id='$project_file_id' AND project_task_id ='$project_task_id'";
$result=db_query($sql);

if ($result && db_numrows($result) > 0) {

    if (db_result($result,0,'filesize') == 0) {

	exit_error('Error','nothing in here - File has a null size');

    } else {
	
	// Download the patch with the correct filetype
	header('Content-Type: '.db_result($result,0,'filetype'));
	header('Content-Length: '.db_result($result,0,'filesize'));
	header('Content-Disposition: filename='.db_result($result,0,'filename'));
	header('Content-Description: '. db_result($result,0,'description'));

	echo db_result($result,0,'file');

    }

} else {
    exit_error('Error',"Couldn't find attached file  (id #$project_file_id)");
}

?>
