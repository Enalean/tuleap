<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
            default :
                throw new User_ForgeUserGroupPermission_NotFoundException();
        }
    }

    /**
     * @return User_ForgeUserGroupPermission[]
     */
    public function getAllAvailableForgePermissions() {
        return array(
            new User_ForgeUserGroupPermission_ProjectApproval()
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
