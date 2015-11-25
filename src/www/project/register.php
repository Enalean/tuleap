<?php
/**
 * Copyright (c) Enalean, 2013 - 2015. All Rights Reserved.
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

ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);

require_once('pre.php');
session_require(array('isloggedin'=>1));

require_once 'vars.php'; //load licenses

$request = HTTPRequest::instance();

$router = new Project_OneStepCreation_OneStepCreationRouter(
    ProjectManager::instance(),
    new Project_CustomDescription_CustomDescriptionFactory(new Project_CustomDescription_CustomDescriptionDao()),
    new TroveCatFactory(new TrovecatDao())
);

$router->route($request);
