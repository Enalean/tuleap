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

require ($DOCUMENT_ROOT.'/include/pre.php');
require ($DOCUMENT_ROOT.'/include/permissions.php');
require ($DOCUMENT_ROOT.'/file/file_utils.php');


if (!user_ismember($group_id,'R2')) {
    exit_permission_denied();
}

$release_id=$_GET['release_id']?$_GET['release_id']:$_POST['object_id'];
$package_id=$_GET['package_id'];


$res=db_query("SELECT * FROM frs_release WHERE release_id=$release_id AND package_id=$package_id");
if (db_numrows($res)<1) {
    exit_error("ERROR", "Release does not exist in this package.");
}
$res2=db_query("SELECT * FROM frs_package WHERE package_id=$package_id");
$package_name=db_result($res2,0,'name');

file_utils_admin_header(array('title'=>'Edit Release Permissions'));
//			 'help' => 'FileReleaseDelivery.html'));



echo '<H3>Release: <a href="/file/admin/editreleases.php?release_id='.$release_id.'&group_id='.$group_id.'">'.
     db_result($res,0,'name') .
     '</a> from package: <a href="/file/admin/editpackages.php?group_id='.$group_id.'">'.
     $package_name.'</a></h3>
<P>
When no permission is defined for a release, then it has the same permissions has the parent package.
<br>When a permission is defined for a release, then it overrides the permissions defined for the package.
<P>';

echo '<h3>Edit release permissions</h3>
<p>Select user groups who are granted access to this release:
<br><b>Note</b>: if you do not specify any access permission, the release inherits the access permissions from <a href="/file/admin/editpackagepermissions.php?package_id='.$package_id.'&group_id='.$group_id.'">the package it belongs to</a> (default setting).
<p>';
$object_id = $_GET['release_id']?$_GET['release_id']:$_POST['object_id'];
$post_url = '/file/admin/editreleases.php?package_id='.$package_id.'&group_id='.$group_id;
permission_display_selection_form("RELEASE_READ", $object_id, $group_id, $post_url);

file_utils_footer(array());

?>
