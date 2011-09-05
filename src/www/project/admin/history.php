<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('www/project/admin/project_history.php');

session_require(array('group'=>$group_id,'admin_flags'=>'A'));


if ($request->exist('export')) {
    export_grouphistory($group_id, $event, $subEvents, $value, $startDate, $endDate, $by, $allSubEvents);
    exit;
}

project_admin_header(array('title'=>$Language->getText('project_admin_history','proj_history'),'group'=>$group_id));

echo $Language->getText('project_admin_history','proj_change_log_msg');

//for pagination
echo show_grouphistory($group_id, $offset, $limit, $event, $subEvents, $value, $startDate, $endDate, $by, $allSubEvents);

project_admin_footer(array());
?>
