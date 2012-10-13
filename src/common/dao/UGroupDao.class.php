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

/**
 *  Data Access Object for UGroup 
 */
class UGroupDao extends DataAccessObject {

    /**
    * Searches static UGroup by GroupId 
    * return all static ugroups
    * @return DataAccessResult
    */
    function searchByGroupId($group_id) {
        $group_id = $this->da->escapeInt($group_id);
        $sql = "SELECT * 
                FROM ugroup 
                WHERE group_id = $group_id ORDER BY name";
        return $this->retrieve($sql);
    }

    function searchDynamicAndStaticByGroupId($group_id) {
        $group_id = $this->da->escapeInt($group_id);
        $sql = "SELECT * 
                FROM ugroup 
                WHERE group_id = $group_id OR (group_id = 100 and ugroup_id <= 100)
                ORDER BY ugroup_id";
        return $this->retrieve($sql);
    }

    function searchByUGroupId($ugroup_id) {
        $ugroup_id = $this->da->escapeInt($ugroup_id);
        $sql = "SELECT * 
                FROM ugroup 
                WHERE ugroup_id = $ugroup_id ORDER BY name";
        return $this->retrieve($sql);
    }

    function searchByGroupIdAndUGroupId($group_id, $ugroup_id) {
        $group_id  = $this->da->escapeInt($group_id);
        $ugroup_id = $this->da->escapeInt($ugroup_id);
        $sql = "SELECT * 
                FROM ugroup 
                WHERE group_id = $group_id AND ugroup_id = $ugroup_id";
        return $this->retrieve($sql);
    }

    function searchByGroupIdAndName($group_id, $name) {
        $group_id  = $this->da->escapeInt($group_id);
        $name      = $this->da->quoteSmart($name);
        $sql = "SELECT *
                FROM ugroup
                WHERE group_id = $group_id AND name = $name";
        return $this->retrieve($sql);
    }

    /**
    * Searches group that user belongs to one of its static ugroup
    * return all groups 
    * @param Integer $userId
    * @return DataAccessResult
    */
    function searchGroupByUserId($userId) {
        $sql = 'SELECT group_id FROM ugroup 
                JOIN ugroup_user USING (ugroup_id) 
                WHERE user_id = '.$this->da->escapeInt($userId);
        return $this->retrieve($sql);
    }

    /**
     * Return all UGroups the user belongs to (cross projects)
     *
     * @param Integrer $userId Id of user
     * 
     * @return DataAccessResult
     */
    function searchByUserId($userId) {
        $sql = 'SELECT ug.*'.
               ' FROM ugroup_user ug_u'.
               '  JOIN ugroup ug USING (ugroup_id)'.
               ' WHERE ug_u.user_id = '.$this->da->quoteSmart($userId);
        return $this->retrieve($sql);
    }

    /**
    * Checks UGroup  validity by GroupId 
    *
    * @param Integer $groupId the group id
    * @param Integer $ugroupId the ugroup id
    *
    * @return boolean
    */
    function checkUGroupValidityByGroupId($groupId, $ugroupId) {
        $groupId = $this->da->escapeInt($groupId);
        $ugroupId = $this->da->escapeInt($ugroupId);

        $sql = 'SELECT NULL
                FROM ugroup 
                WHERE group_id = '. $groupId .' AND ugroup_id = '. $ugroupId;
        $res = $this->retrieve($sql);
        if($res && !$res->isError() && $res->rowCount() == 1) {
            return true;
        } else {
            return false;
        }
    }
}
?>