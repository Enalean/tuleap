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
require_once('www/project/admin/ugroup_utils.php');

/**
 *  Data Access Object for ProjectUGroup 
 */
class UGroupUserDao extends DataAccessObject {

    /**
    * Searches ProjectUGroup members by UGroupId 
    * 
    * Return all Active or Restricted ugroup members
    * Only return active & restricted to keep it coherent with Group::getMembersUserNames
    *
    * @param Integer $ugroup_id Id of the ugroup
    *
    * @return DataAccessResult
    */
    function searchUserByStaticUGroupId($ugroup_id) {
        $ugroup_id = $this->da->escapeInt($ugroup_id);
        $sql = "SELECT * 
                FROM ugroup_user INNER JOIN user USING(user_id) 
                WHERE ugroup_id = $ugroup_id
                AND user.status IN ('A', 'R')
                ORDER BY user_name";
        return $this->retrieve($sql);
    }

    public function searchUserByStaticUGroupIdIncludingSuspended($ugroup_id) {
        $ugroup_id = $this->da->escapeInt($ugroup_id);

        $sql = "SELECT *
                FROM ugroup_user INNER JOIN user USING(user_id)
                WHERE ugroup_id = $ugroup_id
                AND user.status IN ('A', 'R', 'S')
                ORDER BY user_name";

        return $this->retrieve($sql);
    }

    /**
     * Searches ProjectUGroup members by UGroupId paginated
     *
     * Return all Active or Restricted ugroup members
     * Only return active & restricted to keep it coherent with Group::getMembersUserNames
     *
     * @param Integer $ugroup_id Id of the ugroup
     * @param Integer $limit
     * @param Integer $offset
     *
     * @return DataAccessResult
     */
    public function searchUsersByStaticUGroupIdPaginated($ugroup_id, $limit, $offset) {
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
     * @param Integer $ugroup_id Id of the ugroup
     *
     * @return DataAccessResult
     */
    function countUserByStaticUGroupId($ugroup_id) {
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
     * @param Integer $groupId Id of the project
     * @param Array   $ugroups List of ugroups
     * 
     * @return Data Access Result
     */
    function returnProjectAdminsByStaticUGroupId($groupId, $ugroups) {
        $sql = 'SELECT u.email as email FROM user u
                    JOIN ugroup_user uu 
                    USING(user_id)
                    JOIN user_group ug 
                    USING(user_id) 
                    WHERE ug.admin_flags="A" 
                    AND u.status IN ("A", "R") 
                    AND ug.group_id ='.$this->da->escapeInt($groupId).' 
                    AND u.status IN ("A", "R") 
                    AND uu.ugroup_id IN ('.implode(",", $ugroups).')';
        return $this->retrieve($sql);
    }

    /**
     * Get uGroup members for both dynamic & sttic uGroups
     *
     * @param Integer $ugroupId Id of the uGroup
     * @param Integer $groupId  Id of the project
     *
     * @return DataAccessResult
     */
    public function searchUserByDynamicUGroupId($ugroupId, $groupId) {
        $sql = ugroup_db_get_dynamic_members($ugroupId, false, $groupId);
        if (! $sql) {
            return new DataAccessResultEmpty();
        }
        return $this->retrieve($sql);
    }

    public function searchUserByDynamicUGroupIdIncludingSuspended($ugroupId, $groupId) {
        $sql = ugroup_db_get_dynamic_members($ugroupId, false, $groupId, false, null, true);

        if (! $sql) {
            return new DataAccessResultEmpty();
        }

        return $this->retrieve($sql);
    }

    /**
     * Get uGroup members for both dynamic & static uGroups
     *
     * @param Integer $ugroupId Id of the uGroup
     * @param Integer $groupId  Id of the project
     * @param Integer $limit
     * @param Integer $offset
     *
     * @return DataAccessResult | false
     */
    public function searchUsersByDynamicUGroupIdPaginated($ugroupId, $groupId, $limit, $offset) {
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
    public function isDynamicUGroupMember($user_id, $ugroup_id, $group_id) {
        return ugroup_user_is_member($user_id, $ugroup_id, $group_id);
    }

    /**
     * Search users to add to ugroup
     *
     * @param Integer $ugroupId Id of the uGroup
     * @param Array   $filters  List of filters
     *
     * @return Array
     */
    public function searchUsersToAdd($ugroupId, $filters) {
        $ugroup_id              = $this->da->escapeInt($ugroupId);
        $offset                 = $this->da->escapeInt($filters['offset']);
        $number_per_page        = $this->da->escapeInt($filters['number_per_page']);
        $order_by               = (user_get_preference("username_display") > 1 ? 'realname' : 'user_name');
        $join_user_group        = $this->getJoinUserGroup($filters);
        $and_username_filter    = $this->getUsernameFilter($filters);

        $sql = "SELECT SQL_CALC_FOUND_ROWS user.user_id, user_name, realname, email, IF(R.user_id = user.user_id, 1, 0) AS is_on
                FROM user
                    NATURAL LEFT JOIN (SELECT user_id FROM ugroup_user WHERE ugroup_id = $ugroup_id ) AS R
                    $join_user_group
                WHERE status in ('A', 'R')
                  $and_username_filter
                ORDER BY $order_by
                LIMIT $offset, $number_per_page";

        $res  = $this->retrieve($sql);
        $res2 = $this->retrieve('SELECT FOUND_ROWS() as nb');
        $numTotalRows = $res2->getRow();

        return array('result' => $res, 'num_total_rows' => $numTotalRows['nb']);
    }

    private function getJoinUserGroup($filters) {
        $group_id = $this->da->escapeInt($filters['in_project']);
        if ($group_id) {
            return "INNER JOIN user_group ON (
                user_group.user_id = user.user_id
                AND user_group.group_id = $group_id
            )";
        }
        return '';
    }

    private function getUsernameFilter($filters) {
        $username_filters = array(
            $this->getContainsFilter($filters),
            $this->getBeginsWithFilter($filters)
        );
        $username_filters = array_filter($username_filters);
        if ($username_filters) {
            return 'AND ('. implode(' OR ', $username_filters) .')';
        }
        return '';
    }

    private function getContainsFilter($filters) {
        if ($filters['search']) {
            $contain = $this->getDa()->quoteLikeValueSurround($filters['search']);
            return "user.realname LIKE $contain
                OR user.user_name LIKE $contain
                OR user.email LIKE $contain";
        }
    }

    private function getBeginsWithFilter($filters) {
        if ($filters['begin']) {
            $begin = $this->getDa()->quoteLikeValueSuffix($filters['begin']);
            return "user.realname LIKE $begin
                OR user.user_name LIKE $begin
                OR user.email LIKE $begin";
        }
    }

    /**
     * Clone a given user group from another one
     *
     * @param Integer $sourceUgroupId Id of the user group from which we will copy users
     * @param Integer $targetUgroupId Id of the target user group
     *
     * @return Boolean
     */
    public function cloneUgroup($sourceUgroupId, $targetUgroupId) {
        $sourceUgroupId = $this->da->escapeInt($sourceUgroupId);
        $targetUgroupId = $this->da->escapeInt($targetUgroupId);
        $sql            = "INSERT INTO ugroup_user (ugroup_id, user_id)
                             SELECT $targetUgroupId, user_id
                             FROM ugroup_user
                             WHERE ugroup_id = $sourceUgroupId";
        return $this->update($sql);
    }

    /**
     * Remove all users of an ugroup
     *
     * @param Integer $ugroupId Id of the user group
     *
     * @return Boolean
     */
    public function resetUgroupUserList($ugroupId) {
        $ugroupId = $this->da->escapeInt($ugroupId);
        $sql      = "DELETE FROM ugroup_user WHERE ugroup_id = $ugroupId";
        return $this->update($sql);
    }
}

?>