<?php
/**
 * Copyright 1999-2000 (c) The SourceForge Crew
 * Copyright (c) Enalean, 2016 - Present. All rights reserved
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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\ProjectHistoryPresenter;
use Tuleap\Project\Admin\ProjectHistoryResultsPresenter;
use Tuleap\Project\Admin\ProjectHistorySearchPresenter;

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../project/export/project_export_utils.php';
require_once __DIR__ . '/../project/admin/project_history.php';

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

$include_assets = new IncludeAssets(__DIR__ . '/../assets/core', '/assets/core');

$GLOBALS['HTML']->includeFooterJavascriptFile(
    $include_assets->getFileURL('site-admin-project-history.js')
);

$project = ProjectManager::instance()->getProject($group_id ?? 0);

if (! $project || $project->isError()) {
    $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_groupedit', 'error_group'));
    $GLOBALS['Response']->redirect('/admin');
}


if ($request->exist('export')) {
    export_grouphistory($group_id, $event, $subEvents, $value, $startDate, $endDate, $by);
    exit;
}

$dao            = new ProjectHistoryDao();
$history_filter = build_grouphistory_filter($event, $subEvents, $value, $startDate, $endDate, $by);
$results        = $dao->groupGetHistory($offset, $limit, $group_id, $history_filter);

$renderer = new AdminPageRenderer();
$renderer->renderANoFramedPresenter(
    $Language->getText('admin_groupedit', 'title'),
    ForgeConfig::get('codendi_dir') . '/src/templates/admin/projects/',
    'project-history',
    new ProjectHistoryPresenter(
        $project,
        new ProjectHistoryResultsPresenter($results),
        $limit,
        $offset,
        new ProjectHistorySearchPresenter(
            get_history_entries(),
            $event,
            $subEvents,
            $value,
            $startDate,
            $endDate,
            $by
        )
    )
);
