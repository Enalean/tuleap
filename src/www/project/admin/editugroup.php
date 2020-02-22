<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Project\Admin\ProjectUGroup\BindingController;
use Tuleap\Project\Admin\ProjectUGroup\BindingPresenterBuilder;
use Tuleap\Project\Admin\ProjectUGroup\DelegationController;
use Tuleap\Project\Admin\ProjectUGroup\Details\MembersPresenterBuilder;
use Tuleap\Project\Admin\ProjectUGroup\DetailsController;
use Tuleap\Project\Admin\ProjectUGroup\EditBindingUGroupEventLauncher;
use Tuleap\Project\Admin\ProjectUGroup\IndexController;
use Tuleap\Project\Admin\ProjectUGroup\PermissionsDelegationPresenterBuilder;
use Tuleap\Project\Admin\ProjectUGroup\ProjectUGroupPresenterBuilder;
use Tuleap\Project\Admin\ProjectUGroup\UGroupRouter;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDao;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;

require_once __DIR__ . '/../../include/pre.php';

$request = HTTPRequest::instance();

$group_id = $request->getValidated('group_id', 'GroupId', 0);
session_require(array('group' => $group_id, 'admin_flags' => 'A'));

$event_manager                            = EventManager::instance();
$ugroup_manager                           = new UGroupManager();
$ugroup_binding                           = new UGroupBinding(new UGroupUserDao(), $ugroup_manager);
$project_manager                          = ProjectManager::instance();
$edit_event_launcher    = new EditBindingUGroupEventLauncher($event_manager);
$binding_controller                       = new BindingController(
    new ProjectHistoryDao(),
    $project_manager,
    $ugroup_manager,
    $ugroup_binding,
    $request,
    $edit_event_launcher
);
$user_manager                             = UserManager::instance();
$synchronized_project_membership_detector = new SynchronizedProjectMembershipDetector(
    new SynchronizedProjectMembershipDao()
);

$membership_delegation_dao = new MembershipDelegationDao();

$index_controller = new IndexController(
    new ProjectUGroupPresenterBuilder(
        new BindingPresenterBuilder(
            $ugroup_binding,
            $project_manager,
            $user_manager,
            $event_manager
        ),
        new MembersPresenterBuilder($event_manager, new UserHelper(), $synchronized_project_membership_detector),
        new PermissionsDelegationPresenterBuilder($membership_delegation_dao)
    ),
    new IncludeAssets(ForgeConfig::get('tuleap_dir') . '/src/www/assets', '/assets'),
    new HeaderNavigationDisplayer()
);

$details_controller = new DetailsController($request);

$router = new UGroupRouter(
    $ugroup_manager,
    $request,
    $edit_event_launcher,
    $binding_controller,
    new DelegationController($membership_delegation_dao, new ProjectHistoryDao()),
    $index_controller,
    $details_controller,
    $user_manager
);
$router->process();
