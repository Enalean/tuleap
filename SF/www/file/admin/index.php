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



require ('pre.php');    
require ($DOCUMENT_ROOT.'/file/file_utils.php');


if (!user_ismember($group_id,'R2')) {
    exit_permission_denied();
}


file_utils_admin_header(array('title'=>'Release New File Version'));

echo '
<h2>File Manager Administration</h2>
<h3><a href="/file/admin/editpackages.php?group_id='.$group_id.'">Edit/Release Files</a></h3>
<p>
Manage file releases: create or modify packages and releases, set status and access permissions.
<h3><a href="/file/admin/qrs.php?group_id='.$group_id.'">Quick Add File Release</a></h3>
<p>
One step process where you can specify all the necessary information at the same time. The counterpart is that you can only attach one single file to a given release and not several as in the full File Release process.
';
file_utils_footer(array());




?>
