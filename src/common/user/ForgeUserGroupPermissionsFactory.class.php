<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All rights reserved
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

use Tuleap\User\ForgeUserGroupPermission\RetrieveSystemEventsInformationApi;

class User_ForgeUserGroupPermissionsFactory {

    /**
     * @var User_ForgeUserGroupPermissionsDao
     */
    private $permissions_dao;

    public function __construct(User_ForgeUserGroupPermissionsDao $dao) {
        $this->permissions_dao = $dao;
    }

    /**
     * @return User_ForgeUserGroupPermission
     * @throws User_ForgeUserGroupPermission_NotFoundException
     */
    public function getForgePermissionById($permission_id) {
        switch ($permission_id) {
            case User_ForgeUserGroupPermission_ProjectApproval::ID :
                return new User_ForgeUserGroupPermission_ProjectApproval();
            case User_ForgeUserGroupPermission_RetrieveUserMembershipInformation::ID :
                return new User_ForgeUserGroupPermission_RetrieveUserMembershipInformation();
            case User_ForgeUserGroupPermission_TrackerAdminAllProjects::ID :
                return new User_ForgeUserGroupPermission_TrackerAdminAllProjects();
            case User_ForgeUserGroupPermission_MediawikiAdminAllProjects::ID :
                return new User_ForgeUserGroupPermission_MediawikiAdminAllProjects();
            case User_ForgeUserGroupPermission_UserManagement::ID :
                return new User_ForgeUserGroupPermission_UserManagement();
            case RetrieveSystemEventsInformationApi::ID :
                return new RetrieveSystemEventsInformationApi();
            default :
                throw new User_ForgeUserGroupPermission_NotFoundException();
        }
    }

    /**
     * @return User_ForgeUserGroupPermission[]
     */
    public function getAllUnusedForgePermissionsForForgeUserGroup(User_ForgeUGroup $user_group) {
        $unused_permissions    = array();
        $group_permissions_ids = $this->extractPermissionIds($this->permissions_dao->getPermissionsForForgeUGroup($user_group->getId()));
        $all_permissions_ids   = $this->getAllAvailableForgePermissionIds();

        $remaining_permission_ids = array_diff($all_permissions_ids, $group_permissions_ids);

        foreach ($remaining_permission_ids as $remaining_permission_id) {
            $unused_permissions[] = $this->getForgePermissionById($remaining_permission_id);
        }

        return $unused_permissions;
    }

    private function extractPermissionIds($permissions) {
        $permission_ids = array();

        if ($permissions) {
            foreach ($permissions as $permission) {
                $permission_ids[] = $permission['permission_id'];
            }
        }

        return $permission_ids;
    }

    private function getAllAvailableForgePermissionIds() {
        $available_permission_ids = array();

        foreach ($this->getAllAvailableForgePermissions() as $forge_permission) {
            $available_permission_ids[] = $forge_permission->getId();
        }

        return $available_permission_ids;
    }

    public function getAllAvailableForgePermissions() {
        return array(
            new User_ForgeUserGroupPermission_ProjectApproval(),
            new User_ForgeUserGroupPermission_TrackerAdminAllProjects(),
            new User_ForgeUserGroupPermission_MediawikiAdminAllProjects(),
            new User_ForgeUserGroupPermission_RetrieveUserMembershipInformation(),
            new User_ForgeUserGroupPermission_UserManagement(),
            new RetrieveSystemEventsInformationApi()
        );
    }

    /**
     * @return User_ForgeUserGroupPermission[]
     */
    public function getPermissionsForForgeUserGroup(User_ForgeUGroup $user_group) {
        $permissions   = array();
        $user_group_id = $user_group->getId();

        $rows = $this->permissions_dao->getPermissionsForForgeUGroup($user_group_id);

        if (! $rows) {
            return $permissions;
        }

        foreach ($rows as $row) {
            $permissions[$row['permission_id']] = $this->instantiateFromRow($row);
        }

        return array_values($permissions);
    }

    private function instantiateFromRow($row) {
        return $this->getForgePermissionById($row['permission_id']);
    }

}
