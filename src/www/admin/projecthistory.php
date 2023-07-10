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
use Tuleap\Project\Admin\ProjectHistoryPresenter;
use Tuleap\Project\Admin\ProjectHistoryResultsPresenter;
use Tuleap\Project\Admin\ProjectHistorySearchPresenter;

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../project/export/project_export_utils.php';
require_once __DIR__ . '/../project/admin/project_history.php';

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

$include_assets = new \Tuleap\Layout\IncludeAssets(__DIR__ . '/../../scripts/site-admin/frontend-assets', '/assets/core/site-admin');
$GLOBALS['HTML']->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($include_assets, 'site-admin-project-history.js'));

$project = ProjectManager::instance()->getProject($group_id ?? 0);

if (! $project || $project->isError()) {
    $GLOBALS['Response']->addFeedback(Feedback::ERROR, _('Invalid project was passed in.'));
    $GLOBALS['Response']->redirect('/admin');
}


if ($request->exist('export')) {
    export_grouphistory($group_id, $event, $subEvents, $value, $startDate, $endDate, $by);
    exit;
}

$old_value = $value;
if (stristr($old_value, $GLOBALS["Language"]->getText('project_ugroup', 'ugroup_anonymous_users_name_key'))) {
    $old_value = 'ugroup_anonymous_users_name_key';
}
$start_date = null;
if ($startDate) {
    [$timestamp,] = util_date_to_unixtime($startDate);
    $start_date   = new DateTimeImmutable('@' . $timestamp);
}
$end_date = null;
if ($endDate) {
    [$timestamp,] = util_date_to_unixtime($endDate);
    $end_date     = new DateTimeImmutable('@' . $timestamp);
}

$dao     = new ProjectHistoryDao();
$results = $dao->getHistory(
    $project,
    $offset,
    $limit,
    $event,
    $subEvents,
    get_history_entries(),
    $old_value,
    $start_date,
    $end_date,
    $by ? UserManager::instance()->findUser($by) : null,
);

$event_manager = EventManager::instance();

$renderer = new AdminPageRenderer();
$renderer->renderANoFramedPresenter(
    _('Editing Project'),
    ForgeConfig::get('codendi_dir') . '/src/templates/admin/projects/',
    'project-history',
    new ProjectHistoryPresenter(
        $project,
        new ProjectHistoryResultsPresenter($results, $event_manager),
        $limit,
        $offset,
        new ProjectHistorySearchPresenter(
            get_history_entries(),
            $event,
            $subEvents,
            $value,
            $startDate,
            $endDate,
            $by,
            $event_manager,
        )
    )
);
