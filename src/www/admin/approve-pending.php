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

use Tuleap\admin\ProjectEdit\ProjectStatusUpdate;
use Tuleap\Project\Admin\DescriptionFields\ProjectDescriptionFieldBuilder;
use Tuleap\Project\DeletedProjectStatusChangeException;
use Tuleap\Project\Status\CannotDeletedDefaultAdminProjectException;
use Tuleap\Project\Status\SwitchingBackToPendingException;
use Tuleap\User\Admin\PendingProjectBuilder;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Admin\ProjectPendingPresenter;
use Tuleap\Project\DescriptionFieldsDao;
use Tuleap\Project\DescriptionFieldsFactory;

require_once __DIR__ . '/../include/pre.php';

$user                             = UserManager::instance()->getCurrentUser();
$forge_ugroup_permissions_manager = new User_ForgeUserGroupPermissionsManager(
    new User_ForgeUserGroupPermissionsDao()
);
$special_access                   = $forge_ugroup_permissions_manager->doesUserHavePermission(
    $user,
    new User_ForgeUserGroupPermission_ProjectApproval()
);

$request = HTTPRequest::instance();
if (! $special_access) {
    $request->checkUserIsSuperUser();
}

$action = $request->getValidated('action', 'string', '');

$event_manager   = EventManager::instance();
$project_manager = ProjectManager::instance();
$csrf_token      = new CSRFSynchronizerToken('/admin/approve-pending.php');

// group public choice
if ($action == 'activate') {
    $csrf_token->check();
    $groups = [];
    if ($request->exist('list_of_groups')) {
        $groups = array_filter(array_map('intval', explode(",", $request->get('list_of_groups'))));
    }
    foreach ($groups as $group_id) {
        $project = $project_manager->getProject($group_id);
        $project_manager->activateWithNotifications($project);
    }
    $GLOBALS['Response']->redirect('/admin/approve-pending.php');
} elseif ($action == 'delete') {
    $csrf_token->check();
    $group_id = $request->get('group_id');
    $project  = $project_manager->getProject($group_id);
    (new ProjectHistoryDao())->groupAddHistory('deleted', 'x', $project->getID());

    try {
        $project_manager->updateStatus($project, Project::STATUS_DELETED);
    } catch (DeletedProjectStatusChangeException | SwitchingBackToPendingException | CannotDeletedDefaultAdminProjectException $exception) {
        // Do nothing
    }

    $event_manager->dispatch(new ProjectStatusUpdate($project, Project::STATUS_DELETED));
    $GLOBALS['Response']->redirect('/admin/approve-pending.php');
}

$fields_factory  = new DescriptionFieldsFactory(new DescriptionFieldsDao());
$field_builder   = new ProjectDescriptionFieldBuilder($fields_factory);
$project_builder = new PendingProjectBuilder($project_manager, UserManager::instance(), $field_builder, new \Tuleap\Trove\TroveCatCollectionRetriever(new TroveCatDao()));
$project_list    = $project_builder->build();

$siteadmin = new AdminPageRenderer();
$presenter = new ProjectPendingPresenter($project_list, $csrf_token);

$siteadmin->renderAPresenter(
    $GLOBALS['Language']->getText('admin_approve_pending', 'title'),
    ForgeConfig::get('codendi_dir') . '/src/templates/admin/projects/',
    'project-pending',
    $presenter
);
