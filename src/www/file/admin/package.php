<?php
/**
 * SourceForge: Breaking Down the Barriers to Open Source Development
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use Tuleap\FRS\FRSPackageController;
use Tuleap\FRS\FRSPackageRouter;
use Tuleap\FRS\FRSPermissionDao;
use Tuleap\FRS\FRSPermissionFactory;
use Tuleap\FRS\FRSPermissionManager;

require_once('pre.php');

$request         = HTTPRequest::instance();
$project_manager = ProjectManager::instance();

$valid_group_id = new Valid_GroupId();
$valid_group_id->required();
if(! $request->valid($valid_group_id)) {
    exit_no_group();
}

$group_id = $request->get('group_id');
$user     = UserManager::instance()->getCurrentUser();
$project  = $project_manager->getProject($group_id);

$router = new FRSPackageRouter(
    new FRSPackageController(
        FRSPackageFactory::instance(),
        FRSReleaseFactory::instance()
    ),
    FRSPackageFactory::instance(),
    new FRSPermissionManager(
        new FRSPermissionDao(),
        new FRSPermissionFactory(new FRSPermissionDao())
    )
);

$router->route($request, $project, $user);
