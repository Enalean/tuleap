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

require($DOCUMENT_ROOT.'/include/pre.php');
require($DOCUMENT_ROOT.'/project/admin/permissions.php');
require($DOCUMENT_ROOT.'/file/file_utils.php');


if (!user_ismember($group_id,'R2')) {
    exit_permission_denied();
}

$package_id=$_GET['package_id']?$_GET['package_id']:$_POST['object_id'];
$package_name=file_get_package_name_from_id($package_id);
if (!$package_name) {
    exit_error("ERROR", "Package does not exist");
}

file_utils_admin_header(array('title'=>'Edit Package Permissions',
			   'help' => 'FileReleaseDelivery.html'));



echo '<H3>Package:  <a href="/file/admin/editpackages.php?group_id='.$group_id.'">'.$package_name.'</a></h3>
<P>
You can set specific permissions to a package. These permissions apply to all releases and files that belong to this package. But you may also set different permissions to specific releases. 
<p>By default, packages have no specific permissions: access to all files is granted to any CodeX registred user.
<P>';

echo '<h3>Edit package permissions</h3>
<p>Select user groups who are granted access to this package:<p>';

$object_id = $_GET['package_id']?$_GET['package_id']:$_POST['object_id'];
$post_url = '/file/admin/editpackages.php?group_id='.$group_id;
permission_display_selection_form("PACKAGE_READ", $object_id, $group_id, $post_url);


file_utils_footer(array());

?>
