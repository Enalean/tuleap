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

require_once('pre.php');
require_once('www/project/admin/permissions.php');
require('./doc_utils.php');

$Language->loadLanguageMsg('docman/docman');

if ($docid) {

    $query = "select * "
        ."from doc_data "
        ."where docid = $docid";
    
    $result = db_query($query);
    
    if (db_numrows($result) < 1) {
        exit_error($Language->getText('global','error'),
                   $Language->getText('docman_display_doc','error_nodoc',array($docid)));
    } else {
        $row = db_fetch_array($result);
    }


    $from_group_id=$group_id;
    // Get group_id of the document group containing the doc.
    $res_group=db_query("SELECT group_id FROM doc_groups WHERE doc_group=".$row['doc_group']);
    $object_group_id = db_result($res_group,0,'group_id');
    // Visual layout should be that of the document group_id
    $group_id=$object_group_id;

    // Check permissions for document, then document group
    if (permission_exist('DOCUMENT_READ', $docid)) {
        if (!permission_is_authorized('DOCUMENT_READ',$docid,user_getid(),$object_group_id)) {
        exit_error($Language->getText('global','perm_denied'), $Language->getText('global','error_perm_denied'));
        } 
    } else if (!permission_is_authorized('DOCGROUP_READ',$row['doc_group'],user_getid(),$object_group_id)) {
        exit_error($Language->getText('global','perm_denied'), $Language->getText('global','error_perm_denied'));
    } 

    if (user_isloggedin()) {
        //Insert a new entry in the doc_log table only for restricted documents
        $sql = "INSERT INTO doc_log(user_id,docid,time) "
            ."VALUES ('".user_getid()."','".$docid."','".time()."')";
        $res_insert = db_query( $sql );
    }

    // HTML or text files that were copy/pasted are displayed in a CodeX-formatted page.
    // Uploaded files are always displayed as-is.
    if ( (($row['filetype'] == 'text/html')||($row['filetype'] == 'text/plain') )&&($row['filesize']==0)) {
        docman_header(array('title'=>$row['title'],
                            'help'=>'DocumentManager.html'));
        if ($object_group_id != $from_group_id) {
            $group_name=util_get_group_name_from_id($object_group_id);
            print '<H3><span class="feedback">'.$Language->getText('docman_display_doc','warning_different_group',array($group_name)).'</span></H3>';
        }
        // Document data can now contain HTML tags but not php code
        print(util_unconvert_htmlspecialchars($row['data']));
        docman_footer($params);
    } else {
        session_redirect("/docman/download.php?docid=".$docid);
    }
} else {
    exit_error($Language->getText('global','error'),
	       $Language->getText('docman_display_doc','error_wrongid'));
}

