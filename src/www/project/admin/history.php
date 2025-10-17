<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Project\Admin\ProjectHistoryPresenter;
use Tuleap\Project\Admin\ProjectHistoryResultsPresenter;
use Tuleap\Project\Admin\ProjectHistorySearchPresenter;

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

$include_assets = new IncludeAssets(__DIR__ . '/../../../scripts/site-admin/frontend-assets', '/assets/core/site-admin');
$GLOBALS['HTML']->addJavascriptAsset(new JavascriptAsset($include_assets, 'site-admin-project-history.js'));
$GLOBALS['HTML']->addCssAsset(new CssAssetWithoutVariantDeclinaisons($include_assets, 'site-admin-project-history-styles'));

project_admin_header(
    $Language->getText('project_admin_history', 'proj_history'),
    \Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder::DATA_ENTRY_SHORTNAME
);

$project = $request->getProject();

$old_value = $value;
if ($old_value !== null && stristr($old_value, $GLOBALS['Language']->getText('project_ugroup', 'ugroup_anonymous_users_name_key')) !== false) {
    $old_value = 'ugroup_anonymous_users_name_key';
}
$start_date = null;
if ($startDate !== false && $startDate !== '') {
    [$timestamp,] = util_date_to_unixtime($startDate);
    $start_date   = new DateTimeImmutable('@' . $timestamp);
}
$end_date = null;
if ($endDate !== false && $endDate !== '') {
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
    $by !== false ? UserManager::instance()->findUser((string) $by) : null,
);

$event_manager = EventManager::instance();

$renderer = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../templates/admin/projects/');

echo '<h2 class="tlp-framed-horizontally">' . $GLOBALS['Language']->getText('project_admin_utils', 'g_change_history') . '</h2>';
$renderer->renderToPage('project-history-content', new ProjectHistoryPresenter(
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
));

project_admin_footer([]);
