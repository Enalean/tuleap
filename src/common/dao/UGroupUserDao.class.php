<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

require_once __DIR__ . '/../../www/project/admin/ugroup_utils.php';

/**
 *  Data Access Object for ProjectUGroup
 */
class UGroupUserDao extends DataAccessObject
{

    /**
    * Searches ProjectUGroup members by UGroupId
    *
    * Return all Active or Restricted ugroup members
    * Only return active & restricted to keep it coherent with Group::getMembersUserNames
    *
    * @param int $ugroup_id Id of the ugroup
    *
    * @return DataAccessResult
    */
    public function searchUserByStaticUGroupId($ugroup_id)
    {
        $ugroup_id = $this->da->escapeInt($ugroup_id);
        $sql_order = UserHelper::instance()->getDisplayNameSQLOrder();

        $sql = "SELECT *
                FROM ugroup_user INNER JOIN user USING(user_id) 
                WHERE ugroup_id = $ugroup_id
                AND user.status IN ('A', 'R')
                ORDER BY $sql_order";

        return $this->retrieve($sql);
    }

    public function searchUserByStaticUGroupIdIncludingSuspendedAndDeleted($ugroup_id)
    {
        $ugroup_id = $this->da->escapeInt($ugroup_id);

        $sql = "SELECT *
                FROM ugroup_user INNER JOIN user USING(user_id)
                WHERE ugroup_id = $ugroup_id
                AND user.status IN ('A', 'R', 'S', 'D')
                ORDER BY user_name";

        return $this->retrieve($sql);
    }

    /**
     * Searches ProjectUGroup members by UGroupId paginated
     *
     * Return all Active or Restricted ugroup members
     * Only return active & restricted to keep it coherent with Group::getMembersUserNames
     *
     * @param int $ugroup_id Id of the ugroup
     * @param int $limit
     * @param int $offset
     *
     * @return DataAccessResult
     */
    public function searchUsersByStaticUGroupIdPaginated($ugroup_id, $limit, $offset)
    {
        $ugroup_id = $this->da->escapeInt($ugroup_id);
        $limit     = $this->da->escapeInt($limit);
        $offset    = $this->da->escapeInt($offset);

        $sql = "SELECT *
                FROM ugroup_user INNER JOIN user USING(user_id)
                WHERE ugroup_id = $ugroup_id
                  AND user.status IN ('A', 'R')
                ORDER BY user_name ASC
                LIMIT $offset, $limit";

        return $this->retrieve($sql);
    }

    /**
     * Count ProjectUGroup members by UGroupId
     *
     * @param int $ugroup_id Id of the ugroup
     *
     * @return DataAccessResult
     */
    public function countUserByStaticUGroupId($ugroup_id)
    {
        $ugroup_id = $this->da->escapeInt($ugroup_id);

        $sql = "SELECT count(*) AS count_users
                FROM ugroup_user INNER JOIN user USING(user_id)
                WHERE ugroup_id = $ugroup_id
                  AND user.status IN ('A', 'R')
                ORDER BY user_name";

        return $this->retrieve($sql);
    }

    /**
     * Return project admins of given static group
     *
     * @param int $groupId Id of the project
     * @param array   $ugroups List of ugroups
     *
     * @return DataAccessResult|false
     */
    public function returnProjectAdminsByStaticUGroupId($groupId, $ugroups)
    {
        $sql = 'SELECT u.email as email FROM user u
                    JOIN ugroup_user uu 
                    USING(user_id)
                    JOIN user_group ug 
                    USING(user_id) 
                    WHERE ug.admin_flags="A" 
                    AND u.status IN ("A", "R") 
                    AND ug.group_id =' . $this->da->escapeInt($groupId) . ' 
                    AND u.status IN ("A", "R") 
                    AND uu.ugroup_id IN (' . implode(",", $ugroups) . ')';
        return $this->retrieve($sql);
    }

    /**
     * Get uGroup members for both dynamic & sttic uGroups
     *
     * @param int $ugroupId Id of the uGroup
     * @param int $groupId Id of the project
     *
     * @return DataAccessResult
     */
    public function searchUserByDynamicUGroupId($ugroupId, $groupId)
    {
        $sql = ugroup_db_get_dynamic_members($ugroupId, false, $groupId, true);
        if (! $sql) {
            return new DataAccessResultEmpty();
        }
        return $this->retrieve($sql);
    }

    public function searchUserByDynamicUGroupIdIncludingSuspendedAndDeleted($ugroupId, $groupId)
    {
        $sql = ugroup_db_get_dynamic_members($ugroupId, false, $groupId, false, null, true, true);

        if (! $sql) {
            return new DataAccessResultEmpty();
        }

        return $this->retrieve($sql);
    }

    /**
     * Get uGroup members for both dynamic & static uGroups
     *
     * @param int $ugroupId Id of the uGroup
     * @param int $groupId Id of the project
     * @param int $limit
     * @param int $offset
     *
     * @return DataAccessResult | false
     */
    public function searchUsersByDynamicUGroupIdPaginated($ugroupId, $groupId, $limit, $offset)
    {
        $ugroupId = $this->da->escapeInt($ugroupId);
        $groupId  = $this->da->escapeInt($groupId);
        $limit    = $this->da->escapeInt($limit);
        $offset   = $this->da->escapeInt($offset);

        $sql = ugroup_db_get_dynamic_members($ugroupId, false, $groupId, false, null, true);

        if (! $sql) {
            return false;
        }

        $sql .= " LIMIT $offset, $limit"; // Nicolas Terray approved :)

        return $this->retrieve($sql);
    }

    /**
     * @param int $user_id
     * @param int $ugroup_id
     * @param int $group_id
     * @return bool
     */
    public function isDynamicUGroupMember($user_id, $ugroup_id, $group_id)
    {
        return ugroup_user_is_member($user_id, $ugroup_id, $group_id);
    }

    /**
     * Clone a given user group from another one
     *
     * @param int $source_ugroup_id Id of the user group from which we will copy users
     * @param int $target_ugroup_id Id of the target user group
     *
     * @return bool
     */
    public function cloneUgroup($source_ugroup_id, $target_ugroup_id)
    {
        $source_ugroup_id = $this->da->escapeInt($source_ugroup_id);
        $target_ugroup_id = $this->da->escapeInt($target_ugroup_id);

        if ($this->isTargetProjectPrivate($target_ugroup_id)) {
            $sql = "INSERT INTO ugroup_user (ugroup_id, user_id)
                    SELECT $target_ugroup_id, user_id
                    FROM ugroup_user
                      INNER JOIN user_group USING (user_id)
                      INNER JOIN ugroup ON (ugroup.source_id = $source_ugroup_id)
                    WHERE ugroup_user.ugroup_id = $source_ugroup_id
                      AND ugroup.ugroup_id = $target_ugroup_id
                      AND user_group.group_id = ugroup.group_id";
        } else {
            $sql = "INSERT INTO ugroup_user (ugroup_id, user_id)
                    SELECT $target_ugroup_id, user_id
                    FROM ugroup_user
                    WHERE ugroup_id = $source_ugroup_id";
        }

        return $this->update($sql);
    }

    private function isTargetProjectPrivate($target_ugroup_id)
    {
        $private               = $this->da->quoteSmart(Project::ACCESS_PRIVATE);
        $private_wo_restricted = $this->da->quoteSmart(Project::ACCESS_PRIVATE_WO_RESTRICTED);

        $sql = "SELECT *
                FROM ugroup
                INNER JOIN groups USING (group_id)
                WHERE groups.access IN ($private, $private_wo_restricted)
                AND ugroup.ugroup_id = $target_ugroup_id";

        $dar = $this->retrieve($sql);

        return $dar->rowCount() === 1;
    }

    /**
     * Remove all users of an ugroup
     *
     * @param int $ugroupId Id of the user group
     *
     * @return bool
     */
    public function resetUgroupUserList($ugroupId)
    {
        $ugroupId = $this->da->escapeInt($ugroupId);
        $sql      = "DELETE FROM ugroup_user WHERE ugroup_id = $ugroupId";
        return $this->update($sql);
    }
}
