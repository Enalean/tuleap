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
 *  Data Access Object for UGroup 
 */
class UGroupUserDao extends DataAccessObject {

    /**
    * Searches UGroup members by UGroupId 
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
        return $this->retrieve($sql);
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
        $sql = "SELECT SQL_CALC_FOUND_ROWS user.user_id, user_name, realname, email, IF(R.user_id = user.user_id, 1, 0) AS is_on
                FROM user NATURAL LEFT JOIN (SELECT user_id FROM ugroup_user WHERE ugroup_id=". $this->da->escapeInt($ugroupId) .") AS R
                ";
        if ($filters['in_project']) {
            $sql .= " INNER JOIN user_group USING ( user_id ) ";
        }
        $sql .= "
                WHERE status in ('A', 'R') ";
        if ($filters['in_project']) {
            $sql .= " AND user_group.group_id = ". $this->da->escapeInt($filters['in_project']) ." ";
        }
        if ($filters['search'] || $filters['begin']) {
            $sql .= ' AND ( ';
            if ($filters['search']) {
                $sql .= " user.realname LIKE ". $this->da->quoteSmart("%".$filters['search']."%") ." OR user.user_name LIKE ". $this->da->quoteSmart("%".$filters['search']."%") ." OR user.email LIKE ". $this->da->quoteSmart("%".$filters['search']."%") ." ";
                if ($filters['begin']) {
                    $sql .= " OR ";
                }
            }
            if ($filters['begin']) {
                $sql .= " user.realname LIKE ". $this->da->quoteSmart($filters['begin']."%") ." OR user.user_name LIKE ". $this->da->quoteSmart($filters['begin']."%") ." OR user.email LIKE ". $this->da->quoteSmart($filters['begin']."%") ." ";
            }
            $sql .= " ) ";
        }
        $sql .= "ORDER BY ". (user_get_preference("username_display") > 1 ? 'realname' : 'user_name') ."
                LIMIT ". $this->da->escapeInt($filters['offset']) .", ". $this->da->escapeInt($filters['number_per_page']);
        $res  = $this->retrieve($sql);
        $res2 = $this->retrieve('SELECT FOUND_ROWS() as nb');
        $numTotalRows = $res2->getRow();
        return array('result' => $res, 'num_total_rows' => $numTotalRows['nb']);
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