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

    // Only registered users on CodeX can access to restricted documents
    if ( (user_isloggedin())||($row['restricted_access']==0) ) {

    	docman_header($row['title'],$row['title']);
    
    	//print '<pre>'.$row['data'].'</pre>';
    	// LJ Document data can now contain HTML tags and php code
    	// so unescape HTML chars and evaluate the text.
    	eval('?>'.util_unconvert_htmlspecialchars($row['data']));
    	docman_footer($params);

        if ( $row['restricted_access'] == 1 ) {
            //Insert a new entry in the doc_log table only for restricted documents
            $sql = "INSERT INTO doc_log(user_id,docid,time) "
            ."VALUES ('".user_getid()."','".$docid."','".time()."')";
            $res_insert = db_query( $sql );
        }
        
    } else {
    
     /*
        Not logged in
      */
      exit_not_logged_in();
    }

} else {
	exit_error("No document data.","No document to display - invalid or inactive document number.");
}

