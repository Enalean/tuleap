<?php
/**
* Copyright 1999-2000 (c) The SourceForge Crew
* Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

use Tuleap\FRS\FRSReleaseController;
use Tuleap\FRS\FRSReleaseRouter;

require_once __DIR__ . '/../../include/pre.php';

$GLOBALS['HTML']->includeCalendarScripts();
$GLOBALS['HTML']->includeJavascriptFile("../scripts/frs.js");

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

$release_factory = FRSReleaseFactory::instance();
$package_factory = FRSPackageFactory::instance();
$files_factory   = new FRSFileFactory();

$router = new FRSReleaseRouter(
    new  FRSReleaseController(
        $release_factory,
        new User_ForgeUserGroupFactory(new UserGroupDao())
    ),
    $release_factory,
    $package_factory
);

$router->route($request, $project, $user);
