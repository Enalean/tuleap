<?php
/**
  * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

require_once __DIR__ . '/../include/pre.php';

use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\FRSPermissionDao;

$controller = new ForgeAccess_AdminController(
    new CSRFSynchronizerToken($_SERVER['SCRIPT_NAME']),
    new ForgeAccess_ForgePropertiesManager(
        new ConfigDao(),
        ProjectManager::instance(),
        PermissionsManager::instance(),
        EventManager::instance(),
        new FRSPermissionCreator(
            new FRSPermissionDao(),
            new UGroupDao(),
            new ProjectHistoryDao()
        )
    ),
    new Config_LocalIncFinder(),
    new UserDao(),
    $request,
    $GLOBALS['Response']
);
$router = new ForgeAccess_AdminRouter($controller, $request);
$router->route();
