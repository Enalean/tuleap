<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('proj_email.php');


session_require(array('group'=>'1','admin_flags'=>'A'));

$content = "";
$pm = ProjectManager::instance();
$group = $pm->getProject($request->getValidated('group_id', 'uint', 0));
if ($group && is_object($group) && !$group->isError()) {
    if (!send_new_project_email($group)) {
        $GLOBALS['feedback'] .= "<p>".$group->getPublicName()." - ".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin']))."</p>";
    } else {
        $content = "<p>".$Language->getText('admin_newprojectmail','success')."</p>";
    }
}

site_header(array('title'=>$Language->getText('admin_newprojectmail','title')));
print $content;
site_footer(array());
?>
