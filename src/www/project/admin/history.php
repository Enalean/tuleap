<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    
require_once('www/project/admin/project_admin_utils.php');

$Language->loadLanguageMsg('project/project');

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

project_admin_header(array('title'=>$Language->getText('project_admin_history','proj_history'),'group'=>$group_id));

echo $Language->getText('project_admin_history','proj_change_log_msg');

echo show_grouphistory($group_id);

project_admin_footer(array());
?>
