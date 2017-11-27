<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2011 - 2017. All Rights Reserved.
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

use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Project\Admin\ProjectUGroup\BindingController;
use Tuleap\Project\Admin\ProjectUGroup\BindingPresenterBuilder;
use Tuleap\Project\Admin\ProjectUGroup\DetailsController;
use Tuleap\Project\Admin\ProjectUGroup\DynamicUGroupMembersUpdater;
use Tuleap\Project\Admin\ProjectUGroup\EditBindingUGroupEventLauncher;
use Tuleap\Project\Admin\ProjectUGroup\IndexController;
use Tuleap\Project\Admin\ProjectUGroup\MembersController;
use Tuleap\Project\Admin\ProjectUGroup\MembersPresenterBuilder;
use Tuleap\Project\Admin\ProjectUGroup\ProjectUGroupPresenterBuilder;
use Tuleap\Project\Admin\ProjectUGroup\UGroupRouter;
use Tuleap\Project\UserPermissionsDao;

require_once('pre.php');

$request = HTTPRequest::instance();

$group_id = $request->getValidated('group_id', 'GroupId', 0);
session_require(array('group' => $group_id, 'admin_flags' => 'A'));

$event_manager       = EventManager::instance();
$ugroup_manager      = new UGroupManager();
$ugroup_binding      = new UGroupBinding(new UGroupUserDao(), $ugroup_manager);
$project_manager     = ProjectManager::instance();
$edit_event_launcher = new EditBindingUGroupEventLauncher($event_manager);
$binding_controller  = new BindingController(
    new ProjectHistoryDao(),
    $project_manager,
    $ugroup_manager,
    $ugroup_binding,
    $request,
    $edit_event_launcher
);
$user_manager        = UserManager::instance();
$members_controller  = new MembersController(
    $request,
    $user_manager,
    new DynamicUGroupMembersUpdater(new UserPermissionsDao(), $ugroup_binding, $event_manager)

);
$index_controller    = new IndexController(
    new ProjectUGroupPresenterBuilder(
        PermissionsManager::instance(),
        $event_manager,
        new FRSReleaseFactory(),
        new BindingPresenterBuilder(
            $ugroup_binding,
            $project_manager,
            $user_manager,
            $event_manager
        ),
        new MembersPresenterBuilder($event_manager, new UserHelper())
    ),
    new IncludeAssets(ForgeConfig::get('tuleap_dir') . '/src/www/assets', '/assets'),
    new HeaderNavigationDisplayer()
);
$details_controller  = new DetailsController($request);

$router = new UGroupRouter(
    $ugroup_manager,
    $request,
    $edit_event_launcher,
    $binding_controller,
    $members_controller,
    $index_controller,
    $details_controller
);
$router->process();
