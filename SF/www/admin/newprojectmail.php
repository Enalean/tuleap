<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require($DOCUMENT_ROOT.'/include/proj_email.php');

$LANG->loadLanguageMsg('admin/admin');

session_require(array('group'=>'1','admin_flags'=>'A'));

$HTML->header(array('title'=>$LANG->getText('admin_newprojectmail','title')));

send_new_project_email($group_id);

print "<p>".$LANG->getText('admin_newprojectmail','success')."</p>";

$HTML->footer(array());
?>
