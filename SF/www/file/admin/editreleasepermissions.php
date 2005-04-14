<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX, 2001-2004. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
// Originally written by Nicolas Guerin 2004, CodeX Team, Xerox
//

// Simple script to edit release permissions

require_once('pre.php');
require_once('www/project/admin/permissions.php');
require_once('www/file/file_utils.php');
$Language->loadLanguageMsg('file/file');

if (!user_ismember($group_id,'R2')) {
    exit_permission_denied();
}

$release_id=$_GET['release_id']?$_GET['release_id']:$_POST['object_id'];
$package_id=$_GET['package_id'];


$res=db_query("SELECT * FROM frs_release WHERE release_id=$release_id AND package_id=$package_id");
if (db_numrows($res)<1) {
    exit_error($Language->getText('global','error'), $Language->getText('file_admin_editreleasepermissions','rel_not_exist'));
}
$res2=db_query("SELECT * FROM frs_package WHERE package_id=$package_id");
$package_name=db_result($res2,0,'name');

file_utils_admin_header(array('title'=>$Language->getText('file_admin_editreleasepermissions','edit_rel_perm'), 
			 'help' => 'FileReleaseDelivery.html#FileAccessPermissions'));



echo '<H3>'.$Language->getText('file_admin_editreleasepermissions','release').': <a href="/file/admin/editreleases.php?release_id='.$release_id.'&group_id='.$group_id.'">'.
     db_result($res,0,'name') .
     '</a> '.$Language->getText('file_admin_editreleasepermissions','from_p').': <a href="/file/admin/editpackages.php?group_id='.$group_id.'">'.
     $package_name.'</a></h3>
<P>
'.$Language->getText('file_admin_editreleasepermissions','perm_explain').'
<P>';

echo '<h3>'.$Language->getText('file_admin_editreleasepermissions','edit_rel_perm').'</h3>
<p>'.$Language->getText('file_admin_editreleasepermissions','select_u_group',"/file/admin/editpackagepermissions.php?package_id=$package_id&group_id=$group_id")
.'<p>';
$object_id = $release_id;
$post_url = '/file/admin/editreleases.php?package_id='.$package_id.'&group_id='.$group_id;
permission_display_selection_form("RELEASE_READ", $object_id, $group_id, $post_url);

file_utils_footer(array());

?>
