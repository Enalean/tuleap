<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All rights reserved
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

use Tuleap\Admin\PermissionDelegation\PermissionPresenterBuilder;
use Tuleap\User\ForgeUserGroupPermission\SiteAdministratorPermissionChecker;
use Tuleap\User\User_ForgeUserGroupPermissionsFactory;

require_once __DIR__ . '/../include/pre.php';

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

$csrf_token = new CSRFSynchronizerToken('/admin/permission_delegation.php');

$permissions_dao                = new User_ForgeUserGroupPermissionsDao();
$user_group_permissions_factory = new User_ForgeUserGroupPermissionsFactory($permissions_dao, EventManager::instance());
$user_group_permissions_dao     = new User_ForgeUserGroupPermissionsDao();
$user_group_permissions_manager = new User_ForgeUserGroupPermissionsManager($permissions_dao);
$site_admin_permission_checker  = new SiteAdministratorPermissionChecker($user_group_permissions_dao);

$user_group_dao     = new UserGroupDao();
$user_group_factory = new User_ForgeUserGroupFactory($user_group_dao);
$user_group_manager = new User_ForgeUserGroupManager($user_group_dao, $site_admin_permission_checker);

$user_group_users_dao     = new User_ForgeUserGroupUsersDao();
$user_group_users_factory = new User_ForgeUserGroupUsersFactory($user_group_users_dao);
$user_group_users_manager = new User_ForgeUserGroupUsersManager($user_group_users_dao);

$controller = new Admin_PermissionDelegationController(
    $request,
    $csrf_token,
    $user_group_permissions_factory,
    $user_group_permissions_manager,
    $user_group_factory,
    $user_group_manager,
    $user_group_users_factory,
    $user_group_users_manager,
    UserManager::instance(),
    $site_admin_permission_checker,
    new PermissionPresenterBuilder(),
    $user_group_permissions_dao
);

$controller->process();
