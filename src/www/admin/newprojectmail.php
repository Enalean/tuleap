<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: newprojectmail.php 1778 2005-06-24 08:10:38Z nterray $

require_once('pre.php');
require_once('proj_email.php');

$Language->loadLanguageMsg('admin/admin');

session_require(array('group'=>'1','admin_flags'=>'A'));

$content = "";
if (!send_new_project_email($group_id)) {
    $group = group_get_object($group_id);
    if ($group && is_object($group) && !$group->isError()) {
        $GLOBALS['feedback'] .= "<p>".$group->getPublicName()." - ".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin']))."</p>";
    }
} else {
    $content = "<p>".$Language->getText('admin_newprojectmail','success')."</p>";
}

site_header(array('title'=>$Language->getText('admin_newprojectmail','title')));
print $content;
site_footer(array());
?>
