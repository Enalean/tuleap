<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder;

require_once __DIR__ . '/../../include/pre.php';
require_once __DIR__ . '/../admin/project_admin_utils.php';
require_once __DIR__ . '/project_export_utils.php';

// Inherited from old .htaccess
ini_set('max_execution_time', 3600);
ini_set('memory_limit', '256M');

$em       = EventManager::instance();
$request  = HTTPRequest::instance();
$export   = $request->get('export');
$group_id = $request->get('group_id');
if (! isset($export)) {
    $export = "";
}

// Group ID must be defined and must be a project admin
if (! $group_id) {
    exit_error($Language->getText('project_admin_userperms', 'invalid_g'), $Language->getText('project_admin_userperms', 'group_not_exist'));
}

session_require(['group' => $group_id, 'admin_flags' => 'A']);

//  get the Group object
$pm    = ProjectManager::instance();
$group = $pm->getProject($group_id);
if (! $group || ! is_object($group) || $group->isError()) {
    exit_no_group();
}
$atf = new ArtifactTypeFactory($group);
if (! $group || ! is_object($group) || $group->isError()) {
    exit_error($Language->getText('global', 'error'), $Language->getText('project_admin_index', 'not_get_atf'));
}

$project   = $pm->getProject($group_id);
$groupname = $project->getUnixName();
$pg_title  = $Language->getText('project_admin_utils', 'project_data_export') . ' ' . $groupname;

$em->processEvent('project_export', ['export' => $export, 'project' => $project]);

switch ($export) {
    case 'artifact':
        require('./artifact_export.php');
        break;

    case 'artifact_format':
        project_admin_header(['title' => $pg_title], NavigationPresenterBuilder::DATA_ENTRY_SHORTNAME);
        require('./artifact_export.php');
        site_project_footer([]);
        break;

    case 'artifact_history':
        require('./artifact_history_export.php');
        break;

    case 'artifact_history_format':
        project_admin_header(['title' => $pg_title], NavigationPresenterBuilder::DATA_ENTRY_SHORTNAME);
        require('./artifact_history_export.php');
        site_project_footer([]);
        break;

    case 'artifact_deps':
        require('./artifact_deps_export.php');
        break;

    case 'artifact_deps_format':
        project_admin_header(['title' => $pg_title], NavigationPresenterBuilder::DATA_ENTRY_SHORTNAME);
        require('./artifact_deps_export.php');
        site_project_footer([]);
        break;

    case 'access_logs':
        require('./access_logs_export.php');
        break;

    case 'access_logs_format':
        project_admin_header(['title' => $pg_title], NavigationPresenterBuilder::DATA_ENTRY_SHORTNAME);
        require('./access_logs_export.php');
        site_project_footer([]);
        break;

    case 'user_groups':
        require('./user_groups_export.php');
        break;

    case 'user_groups_format':
        project_admin_header(['title' => $pg_title], NavigationPresenterBuilder::DATA_ENTRY_SHORTNAME);
        require('./user_groups_export.php');
        site_project_footer([]);
        break;

    default:
        $GLOBALS['HTML']->redirect('/project/' . urlencode((string) $group_id) . '/admin/export');
        break;
}
