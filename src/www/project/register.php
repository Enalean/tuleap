<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\Project\DefaultProjectVisibilityRetriever;

ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);

require_once __DIR__ . '/../include/pre.php';
session_require(array('isloggedin' => 1));

$request = HTTPRequest::instance();

$router = new Project_OneStepCreation_OneStepCreationRouter(
    ProjectManager::instance(),
    new DefaultProjectVisibilityRetriever(),
    new Project_CustomDescription_CustomDescriptionFactory(new Project_CustomDescription_CustomDescriptionDao()),
    new TroveCatFactory(new TroveCatDao()),
    new \Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker(new ProjectDao())
);

$router->route($request);
