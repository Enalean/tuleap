<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
//	Originally written by Stephane Bouhet 2002, CodeX Team, Xerox
//

require($DOCUMENT_ROOT.'/include/pre.php');

$Language->loadLanguageMsg('docman/docman');

$sql="SELECT description,data,filename,filesize,filetype FROM doc_data WHERE docid='$docid'";
$result=db_query($sql);

if ($result && db_numrows($result) > 0) {

    if (db_result($result,0,'filesize') == 0) {

	exit_error($Language->getText('global','error'),
		   $Language->getText('docman_download','error_nofile'));

    } else {
	
	// Download the patch with the correct filetype
	header('Content-Type: '.db_result($result,0,'filetype'));
	header('Content-Length: '.db_result($result,0,'filesize'));
	header('Content-Disposition: filename='.db_result($result,0,'filename'));

	echo db_result($result,0,'data');

    }

} else {
    exit_error($Language->getText('global','error'),
	       $Language->getText('docman_download','error_nodoc', array($docid)));
}

?>
