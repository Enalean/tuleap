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

require($DOCUMENT_ROOT.'/include/pre.php');
require('./doc_utils.php');

$Language->loadLanguageMsg('docman/docman');

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
	    exit_error($Language->getText('global','error'),
		       $Language->getText('docman_display_doc','error_nodoc',array($docid)));
	} else {
		$row = db_fetch_array($result);
	}

    // Only registered users on CodeX can access to restricted documents
    if ( (user_isloggedin())||($row['restricted_access']==0) ) {

        if ( $row['restricted_access'] == 1 ) {
            //Insert a new entry in the doc_log table only for restricted documents
            $sql = "INSERT INTO doc_log(user_id,docid,time) "
            ."VALUES ('".user_getid()."','".$docid."','".time()."')";
            $res_insert = db_query( $sql );
        }

        // HTML or text files that were copy/pasted are displayed in a CodeX-formatted page.
        // Uploaded files are always displayed as-is.
        if ( (($row['filetype'] == 'text/html')||($row['filetype'] == 'text/plain') )&&($row['filesize']==0)) {
        	docman_header(array('title'=>$row['title']));
        	// Document data can now contain HTML tags and php code
        	// so unescape HTML chars and evaluate the text.
        	eval('?>'.util_unconvert_htmlspecialchars($row['data']));
        	docman_footer($params);
        } else {
            session_redirect("/docman/download.php?docid=".$docid);
        }
               
    } else {
    
     /*
        Not logged in
      */
      exit_not_logged_in();
    }

} else {
    exit_error($Language->getText('global','error'),
	       $Language->getText('docman_display_doc','error_wrongid'));
}

