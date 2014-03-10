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

class User_ForgeUserGroupPermissionsManager {

    /**
     * @var User_ForgeUserGroupPermissionsDao
     */
    private $permissions_dao;

    private $available_permissions = array();

    /**
     *
     * @param int $id
     * @param strin $name
     * @param array $permissions
     * @param string $description
     */
    public function __construct(User_ForgeUserGroupPermissionsDao $dao) {
        $this->permissions_dao = $dao;
    }

    public function addPermission(User_ForgeUGroup $user_group, User_ForgeUserGroupPermission $permission) {
        if (! $this->isPermissionValid($permission->getId())) {
            throw new User_InvalidForgePermissionException('Invalid permission: ' . $permission->getId());
        }

        $permission_name = $this->available_permissions[$permission->getId()];

        $user_group->addPermission($permission->getId(), $permission_name);
    }

    public function deletePermission(User_ForgeUGroup $user_group, User_ForgeUserGroupPermission $permission) {

    }

    public function getPermissionsForForgeUserGroup(User_ForgeUGroup $user_group) {

    }

    /**
     * @param string $permission_id within self::$available_permissions
     * @throws User_InvalidForgePermissionException
     */

    private function isPermissionValid($permission_id) {
        $this->getAvailablePermissions();

        return in_array($permission_id, $this->getAvailablePermissionIds());
    }

    /**
     * Returns an associative $permission_id => $shortname array of permissions
     *
     * @return associative array
     */
    public function getAvailablePermissions() {
        if ($this->available_permissions) {
            return $this->available_permissions;
        }

        $allowed = $this->permissions_dao->getAllowedPermissions();
        if ($allowed) {
            foreach ($allowed as $row) {
                $this->available_permissions[$row['id']] = $row['shortname'];
            }
        }

        return $this->available_permissions;
    }

    public function getAvailablePermissionIds() {
        $this->getAvailablePermissions();

        return array_keys($this->available_permissions);
    }
}
?>
