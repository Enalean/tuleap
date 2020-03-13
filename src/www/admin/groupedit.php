<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Tuleap\admin\ProjectEdit\ProjectEditController;
use Tuleap\admin\ProjectEdit\ProjectEditDao;
use Tuleap\admin\ProjectEdit\ProjectEditRouter;
use Tuleap\Project\Admin\DescriptionFields\ProjectDescriptionFieldBuilder;
use Tuleap\Project\Admin\ProjectDetailsPresenter;
use Tuleap\Project\ProjectAccessPresenter;
use Tuleap\Project\Status\ProjectSuspendedAndNotBlockedWarningCollector;

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/admin_utils.php';
require_once __DIR__ . '/../project/admin/project_admin_utils.php';
require_once __DIR__ . '/../project/export/project_export_utils.php';
require_once __DIR__ . '/../project/admin/project_history.php';

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

$project_manager = ProjectManager::instance();
$event_manager   = EventManager::instance();
$project_id      = $request->get('group_id');
$project         = $project_manager->getProject($project_id);

if (!$project || $project->isError()) {
    $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_groupedit', 'error_group'));
    $GLOBALS['Response']->redirect('/admin');
}

$csrf_token = new CSRFSynchronizerToken('/admin/groupedit.php?group_id=' . urlencode($project_id));

$fields_factory            = new Tuleap\Project\DescriptionFieldsFactory(new Tuleap\Project\DescriptionFieldsDao());
$description_field_builder = new ProjectDescriptionFieldBuilder($fields_factory);
$all_custom_fields         = $description_field_builder->build($project);

$access_presenter     = new ProjectAccessPresenter($project->getAccess());

$suspended_and_not_blocked_warnings = new ProjectSuspendedAndNotBlockedWarningCollector($project);
$event_manager->processEvent($suspended_and_not_blocked_warnings);

$details_presenter    = new ProjectDetailsPresenter(
    $project,
    $all_custom_fields,
    $access_presenter,
    $csrf_token,
    $suspended_and_not_blocked_warnings->getWarnings()
);
$project_edit_dao     = new ProjectEditDao();
$system_event_manager = SystemEventManager::instance();

$edit_controller = new ProjectEditController(
    $details_presenter,
    $project_edit_dao,
    $project_manager,
    $event_manager,
    $system_event_manager,
    new ProjectHistoryDao()
);

$router = new ProjectEditRouter($edit_controller);

$router->route($request, $csrf_token);
