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

class User_ForgeUserGroupPermissionsDao extends DataAccessObject {

    public function permissionExistsForUGroup($user_group_id, $permission_id) {
        $user_group_id = $this->da->escapeInt($user_group_id);
        $permission_id = $this->da->escapeInt($permission_id);

        $sql = "SELECT permission_id FROM ugroup_forge_permission
                WHERE permission_id = $permission_id
                AND ugroup_id = $user_group_id";

        return (bool) $this->retrieve($sql);
    }

    public function addPermission($user_group_id, $permission_id) {
        $user_group_id = $this->da->escapeInt($user_group_id);
        $permission_id = $this->da->escapeInt($permission_id);

        $sql = "INSERT INTO ugroup_forge_permission
                (ugroup_id, permission_id)
                VALUES
                ($user_group_id, $permission_id)";

        return $this->update($sql);
    }

    public function deletePersmissionForUGroup($user_group_id, $permission_id) {
        $user_group_id = $this->da->escapeInt($user_group_id);
        $permission_id = $this->da->escapeInt($permission_id);

        $sql = "DELETE FROM ugroup_forge_permission
                WHERE permission_id = $permission_id
                AND ugroup_id = $user_group_id";

        return $this->update($sql);
    }

    public function getPermissionsForForgeUGroup($user_group_id) {
        $user_group_id = $this->da->escapeInt($user_group_id);

        $sql = "SELECT permission_id FROM ugroup_forge_permission
                WHERE ugroup_id = $user_group_id";

        return $this->retrieve($sql);
    }

}
?>
