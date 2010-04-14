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
}
?>