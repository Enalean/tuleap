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
class UGroupUserDao extends DataAccessObject {

    /**
    * Searches UGroup members by UGroupId 
    * 
    * Return all Active or Restricted ugroup members
    * Only return active & restricted to keep it coherent with Group::getMembersUserNames
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

}
?>