<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Tuleap\Project\Admin\ProjectVisibilityController;
use Tuleap\Project\Admin\ProjectVisibilityRouter;
use Tuleap\Project\Admin\ProjectVisibilityUserConfigurationPermissions;
use Tuleap\Project\Admin\ServicesUsingTruncatedMailRetriever;

require_once('pre.php');

$GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/tuleap/project-visibility.js');

$group_id = $request->get('group_id');
session_require(array('group' => $group_id, 'admin_flags' => 'A'));

if (! $request->getProject()) {
    exit_no_group();
}

$project_visibility_router = new ProjectVisibilityRouter(
    new ProjectVisibilityController(
        ProjectManager::instance(),
        new ProjectVisibilityUserConfigurationPermissions(),
        new ServicesUsingTruncatedMailRetriever(EventManager::instance())
    )
);
$project_visibility_router->route($request);

project_admin_footer(array());
