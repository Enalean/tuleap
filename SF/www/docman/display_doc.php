<?php

//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
//

/*
        Docmentation Manager
        by Quentin Cregan, SourceForge 06/2000
*/

require('pre.php');
require('doc_utils.php');

$usermem = user_ismember($group_id);

if ($docid) {
	$query = "select * "
		."from doc_data "
		."where (docid = $docid "
		."and stateid = '1')";
		// stateid = 1 == active
                if ($usermem == true) {
			$query  .= " or (docid =$docid and stateid = '5')";
			              } //state 5 == 'private' 

	$result = db_query($query);
	
	if (db_numrows($result) < 1) {
		exit_error('Document unavailable','Document is not available.');
	} else {
		$row = db_fetch_array($result);
	}
	
	docman_header($row['title'],$row['title']);

	//print '<pre>'.$row['data'].'</pre>';
	// LJ Document data can now contain HTML tags and php code
	// so unescape HTML chars and evaluate the text.
	eval('?>'.util_unconvert_htmlspecialchars($row['data']));
	docman_footer($params);

} else {
	exit_error("No document data.","No document to display - invalid or inactive document number.");
}
