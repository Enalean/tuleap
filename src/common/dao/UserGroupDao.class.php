<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('include/DataAccessObject.class.php');

class UserGroupDao extends DataAccessObject
{
    /**
    * Searches User-Group by UserId
    * @return DataAccessResult
    */
    public function searchByUserId($user_id)
    {
        $user_id = $this->da->escapeInt($user_id);
        $sql = "SELECT *
                FROM user_group
                WHERE user_id = $user_id";
        return $this->retrieve($sql);
    }

    /**
    * Searches User-Group by UserId
    * @return DataAccessResult
    */
    public function searchActiveGroupsByUserId($user_id)
    {
        $user_id = $this->da->escapeInt($user_id);
        $sql = "SELECT *
                FROM user_group INNER JOIN groups USING(group_id)
                WHERE user_id = $user_id
                  AND groups.status = 'A'";
        return $this->retrieve($sql);
    }

    /**
     * @return DataAccessResult|false
     */
    public function searchActiveProjectsByUserIdAndAccessType(int $user_id, string $access_type)
    {
        $user_id     = $this->da->escapeInt($user_id);
        $access_type = $this->da->quoteSmart($access_type);
        $sql         = "SELECT `groups`.*
                FROM user_group INNER JOIN `groups` USING(group_id)
                WHERE user_id = $user_id
                  AND `groups`.status = 'A' AND `groups`.access = $access_type";
        return $this->retrieve($sql);
    }


    /**
     * return users count, members of given project
     *
     * @param int $groupId
     *
     * @return int
     *
     */
    public function returnUsersNumberByGroupId($groupId)
    {
        $groupId = $this->da->escapeInt($groupId);
        $sql = 'SELECT count(*) as numrows
                FROM user_group
                WHERE group_id =' . $groupId;
        $row = $this->retrieve($sql)->getRow();
        return $row['numrows'];
    }

    /**
     * Return project admins of given project
     *
     * @param int $groupId
     *
     * @return DataAccessResult
     */
    public function returnProjectAdminsByGroupId($groupId)
    {
        $sql = 'SELECT u.email as email  FROM user u
                    JOIN user_group ug
                    USING(user_id)
                    WHERE ug.admin_flags="A"
                    AND u.status IN ("A", "R")
                    AND ug.group_id =' . $this->da->escapeInt($groupId);
        return $this->retrieve($sql);
    }

    public function searchProjectAdminsByProjectIdExcludingOneUserId($project_id, $user_id)
    {
        $project_id = $this->getDa()->escapeInt($project_id);
        $user_id    = $this->getDa()->escapeInt($user_id);
        $sql = "SELECT u.email as email  FROM user u
                    JOIN user_group ug
                    USING(user_id)
                    WHERE ug.admin_flags='A'
                    AND u.status IN ('A', 'R')
                    AND ug.group_id = $project_id AND u.user_id != $user_id";
        return $this->retrieve($sql);
    }

    /**
     * Remove users from a given project
     *
     * @param int $groupId
     *
     * @return bool
     */
    public function removeProjectMembers($groupId)
    {
        $groupId = $this->da->escapeInt($groupId);
        $sql     = "DELETE FROM user_group" .
                   " WHERE group_id = " . $groupId;
        return $this->update($sql);
    }

    public function updateUserGroupFlags($user_id, $group_id, $flag)
    {
        if ($flag == '') {
            return false;
        }

        // FIXME: find a way to escape the flag to prevent mysql injection
        //        for now it is not possible but we don't
        //        necessarily know who will use this dao.
        $user_id  = $this->da->escapeInt($user_id);
        $group_id = $this->da->escapeInt($group_id);
        $sql = "UPDATE user_group
                SET $flag
                WHERE group_id = $group_id
                  AND user_id = $user_id";
        return $this->update($sql);
    }

    /**
     * Return name and id of all ugroups belonging to a specific project
     *
     * @param int $groupId Id of the project
     * @param Array   $predefined List of predefined ugroup id
     *
     * @return DataAccessResult|false
     */
    public function getExistingUgroups($groupId, $predefined = null)
    {
        $extra = '';
        if ($predefined !== null && is_array($predefined)) {
            $predefined = implode(',', $predefined);
            $extra = ' OR ugroup_id IN (' . $this->da->quoteSmart($predefined) . ')';
        }
        $sql = "SELECT *
              FROM ugroup
              WHERE group_id=" . $this->da->escapeInt($groupId) . "
                " . $extra . "
              ORDER BY name";
        return $this->retrieve($sql);
    }

    public function getAllForgeUGroups()
    {
        $sql = "SELECT ugroup.* FROM ugroup
                WHERE ugroup.group_id IS NULL";

        return $this->retrieve($sql);
    }

    public function getForgeUGroup($user_group_id)
    {
        $user_group_id = $this->da->escapeInt($user_group_id);

        $sql = "SELECT ugroup.* FROM ugroup
                WHERE ugroup.ugroup_id = $user_group_id
                AND ugroup.group_id IS NULL";

        return $this->retrieveFirstRow($sql);
    }

    /**
    * @return bool
    * @throws User_UserGroupNameInvalidException
    */
    public function updateForgeUGroup($user_group_id, $name, $description)
    {
        if (! $this->isUserGroupNameValid($name, $user_group_id)) {
            throw new User_UserGroupNameInvalidException($name);
        }

        $user_group_id = $this->da->escapeInt($user_group_id);
        $description   = $this->da->quoteSmart($description);
        $name          = $this->da->quoteSmart($name);

        $sql = "UPDATE ugroup
            SET name = $name,
            description = $description
            WHERE ugroup_id = $user_group_id
            AND ugroup.group_id IS NULL";

        return $this->update($sql);
    }

    /**
     * @return int
     * @throws User_UserGroupNameInvalidException
     */
    public function createForgeUGroup($name, $description)
    {
        if (! $this->isUserGroupNameValid($name, null)) {
            throw new User_UserGroupNameInvalidException($name);
        }

        $name        = $this->da->quoteSmart($name);
        $description = $this->da->quoteSmart($description);

        $sql = "INSERT INTO ugroup
                    (name, description, group_id)
                VALUES
                 ($name, $description, NULL)";

        return $this->updateAndGetLastId($sql);
    }

    private function isUserGroupNameValid($name, $user_group_id)
    {
        if (! $name) {
            return false;
        }

        $name = $this->getDa()->quoteSmart($this->getDa()->escapeLikeValue($name));

        $sql = "SELECT ugroup.ugroup_id FROM ugroup WHERE name LIKE $name";
        $row = $this->retrieveFirstRow($sql);

        if (! $row) {
            return true;
        }

        if ($user_group_id && $row['ugroup_id'] == $user_group_id) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function deleteForgeUGroup($user_group_id)
    {
        $user_group_id = $this->da->escapeInt($user_group_id);

        $this->startTransaction();

        $sql = "DELETE FROM ugroup_user
                WHERE ugroup_id = $user_group_id";

        if (! $this->update($sql)) {
            $this->rollback();
            return false;
        }

        $sql = "DELETE FROM ugroup_forge_permission
                WHERE ugroup_id = $user_group_id";

        if (! $this->update($sql)) {
            $this->rollback();
            return false;
        }

        $sql = "DELETE FROM ugroup
                WHERE ugroup_id = $user_group_id
                AND ugroup.group_id IS NULL";

        if (! $this->update($sql)) {
            $this->rollback();
            return false;
        }

        $this->commit();
        return true;
    }

    public function getDynamicForgeUserGroupByName($name)
    {
        $name = $this->da->quoteSmart($name);

        $sql = "SELECT * FROM ugroup WHERE name = $name";
        return $this->retrieveFirstRow($sql);
    }
}
