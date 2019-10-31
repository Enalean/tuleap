<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
 * SourceForge: Breaking Down the Barriers to Open Source Development
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
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementDao;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;

require_once __DIR__ . '/../../include/pre.php';

$request         = HTTPRequest::instance();
$project_manager = ProjectManager::instance();

$valid_group_id = new Valid_GroupId();
$valid_group_id->required();
if (! $request->valid($valid_group_id)) {
    exit_no_group();
}

$group_id = $request->get('group_id');
$user     = UserManager::instance()->getCurrentUser();
$project  = $project_manager->getProject($group_id);

$router = new FRSPackageRouter(
    new FRSPackageController(
        FRSPackageFactory::instance(),
        FRSReleaseFactory::instance(),
        new User_ForgeUserGroupFactory(new UserGroupDao()),
        PermissionsManager::instance(),
        new LicenseAgreementFactory(
            new LicenseAgreementDao()
        ),
    ),
    FRSPackageFactory::instance(),
    FRSPermissionManager::build()
);

$router->route($request, $project, $user);
