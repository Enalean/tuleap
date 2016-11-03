<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

require_once 'pre.php';

$project_manager = ProjectManager::instance();
$token_manager   = new SVN_TokenUsageManager(new SVN_TokenDao(), $project_manager);
$event_manager   = EventManager::instance();

$GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/tuleap/manage-allowed-projects-on-resource.js');

$controller = new SVN_Admin_Controller($project_manager, $token_manager, $event_manager);
$action     = $request->get('action');
switch ($action) {
    case 'index':
        $controller->getAdminIndex($request);
        break;
    case 'update_project':
        $controller->updateProject($request);
}
