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

require_once('pre.php');
require_once('www/project/admin/permissions.php');

$Language->loadLanguageMsg('docman/docman');


$sql="SELECT description,data,filename,filesize,filetype,doc_group FROM doc_data WHERE docid='$docid'";
$result=db_query($sql);

if ($result && db_numrows($result) > 0) {

    // Get group_id of the document group containing the doc.
    $res_group=db_query("SELECT group_id FROM doc_groups WHERE doc_group=".db_result($result,0,'doc_group') );
    $object_group_id = db_result($res_group,0,'group_id');

    // Check permissions for document, then document group
    if (permission_exist('DOCUMENT_READ', $docid)) {
        if (!permission_is_authorized('DOCUMENT_READ',$docid,user_getid(),$object_group_id)) {
            exit_error($Language->getText('global','perm_denied'), $Language->getText('global','error_perm_denied'));
        } 
    } else if (!permission_is_authorized('DOCGROUP_READ',db_result($result,0,'doc_group'),user_getid(),$object_group_id)) {
        exit_error($Language->getText('global','perm_denied'), $Language->getText('global','error_perm_denied'));
    } 



    if (db_result($result,0,'filesize') == 0) {
	exit_error($Language->getText('global','error'),
		   $Language->getText('docman_download','error_nofile'));
    } else {
	
	// Download the patch with the correct filetype
	header('Content-Type: '.db_result($result,0,'filetype'));
	header('Content-Length: '.db_result($result,0,'filesize'));
	header('Content-Disposition: filename="'.db_result($result,0,'filename').'"');

	echo db_result($result,0,'data');

    }

} else {
    exit_error($Language->getText('global','error'),
	       $Language->getText('docman_download','error_nodoc', array($docid)));
}

?>
