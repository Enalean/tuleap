<?php
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
require_once __DIR__ . '/../../include/pre.php';
require_once __DIR__ . '/project_admin_utils.php';
require_once __DIR__ . '/../export/project_export_utils.php';
require_once __DIR__ . '/project_history.php';

$group_id = $request->getValidated('group_id', 'uint', 0);
session_require(['group' => $group_id, 'admin_flags' => 'A']);


// $events, $subEvents and so on are declared in project_history.php
if ($request->exist('export')) {
    export_grouphistory($group_id, $event, $subEvents, $value, $startDate, $endDate, $by);
    exit;
}

project_admin_header(
    $Language->getText('project_admin_history', 'proj_history'),
    \Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder::DATA_ENTRY_SHORTNAME
);

echo $Language->getText('project_admin_history', 'proj_change_log_msg');

//for pagination
echo show_grouphistory($group_id, $offset, $limit, $event, $subEvents, $value, $startDate, $endDate, $by);

project_admin_footer([]);
