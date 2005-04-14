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



require_once('pre.php');    
require_once('www/file/file_utils.php');


if (!user_ismember($group_id,'R2')) {
    exit_permission_denied();
}


file_utils_admin_header(array('title'=>$Language->getText('file_admin_editreleases','release_new_file_version')));

echo '
<h2'.$Language->getText('file_admin_index','file_manager_admin').'</h2>
<h3><a href="/file/admin/editpackages.php?group_id='.$group_id.'">'.$Language->getText('file_admin_index','edit_release_files').'</a></h3>
<p>
'.$Language->getText('file_admin_index','manage_explain').'
<h3><a href="/file/admin/qrs.php?group_id='.$group_id.'">'.$Language->getText('file_admin_index','quick_add').'</a></h3>
<p>
'.$Language->getText('file_admin_index','quick_add_explain');
file_utils_footer(array());




?>
