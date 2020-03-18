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

require_once __DIR__ . '/../../include/pre.php';
require_once __DIR__ . '/../file_utils.php';

use Tuleap\FRS\FRSRouter;
use Tuleap\FRS\PermissionController;
use Tuleap\FRS\FRSPermissionFactory;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\FRSPermissionDao;

$vGroupId = new Valid_GroupId();
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_error($Language->getText('file_file_utils', 'g_id_err'), $Language->getText('file_file_utils', 'g_id_err'));
    exit();
}

$permission_dao     = new FRSPermissionDao();
$permission_factory = new FRSPermissionFactory(
    $permission_dao
);
$permission_manager = FRSPermissionManager::build();

$project_manager = ProjectManager::instance();
$project         = $project_manager->getProject($group_id);
$user = UserManager::instance()->getCurrentUser();
if (! $permission_manager->isAdmin($project, $user)) {
    exit_permission_denied();
}

$frs_router = new FRSRouter(
    new PermissionController(
        $permission_factory,
        new FRSPermissionCreator(
            $permission_dao,
            new UGroupDao(),
            new ProjectHistoryDao()
        ),
        $permission_manager,
        new User_ForgeUserGroupFactory(new UserGroupDao())
    )
);

$frs_router->route($request, $project);
