<?php
/**
 * Copyright 1999-2000 (c) The SourceForge Crew
 * Copyright (c) Enalean, 2016. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

require_once('pre.php');
require_once('www/project/export/project_export_utils.php');
require_once('www/project/admin/project_history.php');

session_require(array('group' => '1', 'admin_flags' => 'A'));

$project = ProjectManager::instance()->getProject($group_id);

if (! $project || $project->isError()) {
    $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_groupedit', 'error_group'));
    $GLOBALS['Response']->redirect('/admin');
}


if ($request->exist('export')) {
    export_grouphistory($group_id, $event, $subEvents, $value, $startDate, $endDate, $by);
    exit;
}

echo show_grouphistory($group_id, $offset, $limit, $event, $subEvents, $value, $startDate, $endDate, $by);
