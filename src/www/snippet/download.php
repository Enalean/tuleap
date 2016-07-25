<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// Copyright (c) Enalean, 2015 - 2016. All Rights Reserved.
// http://sourceforge.net
//
// 

require_once('pre.php');

$id = (int)$request->get('id');

$sql="SELECT * FROM snippet_version WHERE snippet_version_id='". db_ei($id) ."'";
$result=db_query($sql);

if ($result && db_numrows($result) > 0) {
    header('X-Content-Type-Options: nosniff');

   if (db_result($result,0,'filesize') == 0) {

	// if snippet was just copy-pasted then show it directly
	header('Content-Type: text/plain');
	if (strlen(db_result($result,0,'code')) > 1) {
		echo util_unconvert_htmlspecialchars( db_result($result,0,'code') );
	} else {
		echo $Language->getText('snippet_download','nothing_in_here');
	}

   } else {

	// Download the patch with the correct filetype
	if (isset($mode) && $mode == 'view') {
        header('Content-Type: text/plain');
    } else {
        header('Content-Disposition: attachment; filename='.db_result($result, 0 ,'filename'));
        header('Content-Type: '.db_result($result, 0, 'filetype'));
    }

	echo db_result($result,0,'code');

    }

} else {
	echo $Language->getText('global','error');
}
