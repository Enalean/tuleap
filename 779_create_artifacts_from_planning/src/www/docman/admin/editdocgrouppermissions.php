<?php
//
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
// 
//
// Originally written by Nicolas Guerin 2005, Codendi Team, Xerox
//

// Simple script to edit document groups permissions

require_once('pre.php');
require_once('www/project/admin/permissions.php');
require('../doc_utils.php');


if (!(user_ismember($group_id,"D2"))) {
    $feedback .= $Language->getText('docman_admin_index','error_perm');
    exit_permission_denied();
}

$doc_group=$_GET['doc_group']?$_GET['doc_group']:$_POST['object_id'];


$query = "select * from doc_groups "
    ."where doc_group='$doc_group' ";
$result = db_query($query);
$row = db_fetch_array($result);

docman_header_admin(array('title'=>$Language->getText('docman_admin_editdocgrouppermissions','title'), 
                          'help' => 'DocumentAdministration.html#DocAccessPermissions'));

echo '<H3>'.$Language->getText('docman_doc_utils','doc_group').': <a href="/docman/admin/index.php?mode=groupedit&doc_group='.$doc_group.'&group_id='.$group_id.'">'.
     $row['groupname'].
     '</a></h3>

<p>'.$Language->getText('docman_admin_editdocgrouppermissions','introduction').'</P>';

echo '<h3>'.$Language->getText('docman_admin_editdocgrouppermissions','title').'</h3>
<p>'.$Language->getText('docman_admin_editdocgrouppermissions','instructions').'<p>';
$object_id = $doc_group;
$post_url = '/docman/admin/index.php?doc_group='.$doc_group.'&mode=editgroups&group_id='.$group_id;
permission_display_selection_form("DOCGROUP_READ", $object_id, $group_id, $post_url);

docman_footer(array());

?>
