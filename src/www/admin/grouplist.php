<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Project\Admin\ProjectListPresenter;
use Tuleap\Project\Admin\ProjectListResultsPresenterBuilder;
use Tuleap\Project\Admin\ProjectListSearchFieldsPresenterBuilder;

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/admin_utils.php';

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

$include_assets = new \Tuleap\Layout\IncludeAssets(__DIR__ . '/../../scripts/site-admin/frontend-assets', '/assets/core/site-admin');
$GLOBALS['HTML']->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($include_assets, 'site-admin-project-list.js'));

//EXPORT-CSV
if ($request->exist('export')) {
    //Validate group_name_search
    $group_name_search       = "";
    $valid_group_name_search = new Valid_String('group_name_search');
    if ($request->valid($valid_group_name_search)) {
        $group_name_search = $request->get('group_name_search');
    }
    //Get status values
    $status_values = [];
    if ($request->exist('status')) {
        $status_values = $request->get('status');
        if (! is_array($status_values)) {
            $status_values = explode(',', $status_values);
        }
    }
    if (in_array('ANY', $status_values)) {
        $status_values = [];
    }
    //export user list in csv format
    $project_list_exporter = new Admin_ProjectListExporter();
    $project_list_csv      = $project_list_exporter->exportProjectList($group_name_search, $status_values);
    header('Content-Type: text/csv');
    header('Content-Disposition:attachment; filename=project_list.csv');
    header('Content-Length:' . strlen($project_list_csv));
    echo $project_list_csv;
    exit;
}

$dao    = new ProjectDao(CodendiDataAccess::instance());
$offset = $request->getValidated('offset', 'uint', 0);
if (! $offset || $offset < 0) {
    $offset = 0;
}
$limit = 50;

$group_name_search = "";
$vGroupNameSearch  = new Valid_String('group_name_search');
if ($request->valid($vGroupNameSearch)) {
    if ($request->exist('group_name_search')) {
        $group_name_search = $request->get('group_name_search');
    }
}

$status_values = [];
if ($request->exist('status')) {
    $status_values = $request->get('status');
    if (! is_array($status_values)) {
        $status_values = explode(',', $status_values);
    }
} else {
    $status_values = ['ANY'];
}

$dao_status_values = $status_values;
if (in_array('ANY', $status_values)) {
    $dao_status_values = [];
}

//return projects matching given parameters
$projects          = $dao->searchProjectsWithNumberOfMembers(
    $offset,
    $limit,
    $dao_status_values,
    $group_name_search
);
$total_nb_projects = $dao->getFoundRows();

if ($total_nb_projects == 1) {
    $row = $projects->getRow();
    $GLOBALS['Response']->redirect('/admin/groupedit.php?group_id=' . $row['group_id']);
}

$title = $Language->getText('admin_projectlist', 'project_list');

$search_fields_builder   = new ProjectListSearchFieldsPresenterBuilder();
$search_fields_presenter = $search_fields_builder->build(
    $group_name_search,
    $status_values
);

$results_builder   = new ProjectListResultsPresenterBuilder();
$results_presenter = $results_builder->build(
    $projects,
    $total_nb_projects,
    $group_name_search,
    $status_values,
    $limit,
    $offset
);

$pending_projects_count = ProjectManager::instance()->countProjectsByStatus(Project::STATUS_PENDING);

$project_list_presenter = new ProjectListPresenter(
    $title,
    $search_fields_presenter,
    $results_presenter,
    $pending_projects_count
);

$admin_page = new AdminPageRenderer();
$admin_page->renderAPresenter(
    $Language->getText('admin_projectlist', 'title'),
    ForgeConfig::get('codendi_dir') . '/src/templates/admin/projects/',
    'projectlist',
    $project_list_presenter
);
