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

class User_ForgeUserGroupUsersDao extends DataAccessObject
{

    public function getUsersByForgeUserGroupId($ugroup_id)
    {
        $ugroup_id = $this->da->escapeInt($ugroup_id);

        $sql = "SELECT DISTINCT user.* FROM ugroup_user
                    JOIN ugroup ON ugroup_user.ugroup_id = ugroup.ugroup_id
                    JOIN user ON ugroup_user.user_id = user.user_id
                WHERE ugroup.ugroup_id = $ugroup_id
                    AND user.status IN ('A', 'R')
                AND ugroup.group_id IS NULL";

        return $this->retrieve($sql);
    }

    public function addUserToForgeUserGroup($user_id, $ugroup_id)
    {
        $user_id   = $this->da->escapeInt($user_id);
        $ugroup_id = $this->da->escapeInt($ugroup_id);

        $sql = "INSERT INTO ugroup_user
                (user_id, ugroup_id)
                VALUES ($user_id, $ugroup_id)";

        return $this->update($sql);
    }

    public function removeUserFromForgeUserGroup($user_id, $ugroup_id)
    {
        $user_id   = $this->da->escapeInt($user_id);
        $ugroup_id = $this->da->escapeInt($ugroup_id);

        $sql = "DELETE FROM ugroup_user
                WHERE ugroup_id = $ugroup_id
                    AND user_id = $user_id";

        return $this->update($sql);
    }

    public function isUserInGroup($user_id, $ugroup_id)
    {
        $user_id   = $this->da->escapeInt($user_id);
        $ugroup_id = $this->da->escapeInt($ugroup_id);

        $sql = "SELECT user_id
                FROM ugroup_user
                WHERE user_id = $user_id
                    AND ugroup_id = $ugroup_id
                LIMIT 1";

        return count($this->retrieve($sql)) > 0;
    }
}
