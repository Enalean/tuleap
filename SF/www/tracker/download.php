<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
//	Originally written by Laurent Julliard 2001, 2002, CodeX Team, Xerox
//

require_once('pre.php');
$Language->loadLanguageMsg('tracker/tracker');

$sql="SELECT description,bin_data,filename,filesize,filetype FROM artifact_file WHERE id='$id' AND artifact_id ='$artifact_id'";
//echo $sql;
$result=db_query($sql);

if ($result && db_numrows($result) > 0) {

    if (db_result($result,0,'filesize') == 0) {

	exit_error($Language->getText('global', 'error'),$Language->getText('tracker_download','file_is_null');

    } else {
	
	// Download the patch with the correct filetype
	header('Content-Type: '.db_result($result,0,'filetype'));
	header('Content-Length: '.db_result($result,0,'filesize'));
	header('Content-Disposition: filename='.db_result($result,0,'filename'));
	header('Content-Description: '. db_result($result,0,'description'));

	echo db_result($result,0,'bin_data');

    }

} else {
    exit_error($Language->getText('global', 'error'),$Language->getText('tracker_download', 'file_not_found', $id);
}

?>
