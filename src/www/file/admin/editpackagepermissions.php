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

// Simple script to edit package permissions

require_once('pre.php');
require_once('www/project/admin/permissions.php');
require_once('common/frs/FRSPackageFactory.class.php');
require_once('www/file/file_utils.php');
$Language->loadLanguageMsg('file/file');

if (!user_ismember($group_id,'R2')) {
    exit_permission_denied();
}

$package_id=$_GET['package_id']?$_GET['package_id']:$_POST['object_id'];
$frspf = new FRSPackageFactory();
$package =& $frspf->getFRSPackageFromDb($package_id);
$package_name = $package->getName();
if (!$package_name) {
    exit_error($Language->getText('global','error'), $Language->getText('file_admin_editpackagepermissions','p_not_exist'));
}

file_utils_admin_header(array('title'=>$Language->getText('file_admin_editpackagepermissions','edit_p_perm'),
			   'help' => 'FileReleaseDelivery.html#FileAccessPermissions'));



echo '<H3>'.$Language->getText('file_admin_editpackagepermissions','p').':  <a href="/file/admin/editpackages.php?group_id='.$group_id.'">'.$package_name.'</a></h3>
<P>
'.$Language->getText('file_admin_editpackagepermissions','perm_explain').'
<P>';

echo '<h3>'.$Language->getText('file_admin_editpackagepermissions','edit_p_perm').'</h3>
<p>'.$Language->getText('file_admin_editpackagepermissions','select_u_group').':<p>';

$object_id = $_GET['package_id']?$_GET['package_id']:$_POST['object_id'];
$post_url = '/file/admin/editpackages.php?group_id='.$group_id;
permission_display_selection_form("PACKAGE_READ", $object_id, $group_id, $post_url);


file_utils_footer(array());

?>
