<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX, 2001-2005. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
// Originally written by Nicolas Guerin 2005, CodeX Team, Xerox
//

// Simple script to edit document permissions

require_once('pre.php');
require_once('www/project/admin/permissions.php');
require('../doc_utils.php');

$Language->loadLanguageMsg('docman/docman');

if (!(user_ismember($group_id,"D2"))) {
    $feedback .= $Language->getText('docman_admin_index','error_perm');
    exit_permission_denied();
}

$docid=$_GET['docid']?$_GET['docid']:$_POST['object_id'];


$query = "select * from doc_data,doc_groups "
    ."where docid='$docid' "
    ."and doc_groups.doc_group = doc_data.doc_group "
    ."and doc_groups.group_id = '$group_id'";
$result = db_query($query);
$row = db_fetch_array($result);

docman_header_admin(array('title'=>$Language->getText('docman_admin_editdocpermissions','title'), 
                          'help' => 'DocumentAdministration.html#DocAccessPermissions'));

echo '<H3>'.$Language->getText('docman_admin_editdocpermissions','doc_title').': <a href="/docman/display_doc.php?docid='.$docid.'&group_id='.$group_id.'">'.
     $row['title'].
     '</a></h3>
<P>'.$Language->getText('docman_admin_editdocpermissions','instructions_intro').'<P>';

echo '<h3>'.$Language->getText('docman_admin_editdocpermissions','title').'</h3>
<p>'.$Language->getText('docman_admin_editdocpermissions','instructions',array('/docman/admin/editdocgrouppermissions.php?doc_group='.$row['doc_group'].'&group_id='.$group_id)).'<p>';
$object_id = $docid;
$post_url = '/docman/admin/index.php?docid='.$docid.'&group_id='.$group_id;
permission_display_selection_form("DOCUMENT_READ", $object_id, $group_id, $post_url);

docman_footer(array());

?>
