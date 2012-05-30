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

class UserGroupDao extends DataAccessObject {
    /**
    * Searches User-Group by UserId 
    * @return DataAccessResult
    */
    function searchByUserId($user_id) {
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
    function searchActiveGroupsByUserId($user_id) {
        $user_id = $this->da->escapeInt($user_id);
        $sql = "SELECT * 
                FROM user_group INNER JOIN groups USING(group_id)
                WHERE user_id = $user_id
                  AND groups.status = 'A'";
        return $this->retrieve($sql);
    }
    
    
    /**
     * return users count, members of given project
     *
     * @param Integer $groupId
     *
     * @return Integer
     *        
     */
    function returnUsersNumberByGroupId($groupId) {
        $groupId = $this->da->escapeInt($groupId);
        $sql = 'SELECT count(*) as numrows 
                FROM user_group 
                WHERE group_id ='.$groupId;
        $row = $this->retrieve($sql)->getRow();
        return $row['numrows'];
    }

    /**
     * Return project admins of given project
     *
     * @param Integer $groupId
     *
     * @return Data Access Result
     */
    function returnProjectAdminsByGroupId($groupId) {
        $sql = 'SELECT u.email as email  FROM user u
                    JOIN user_group ug 
                    USING(user_id) 
                    WHERE ug.admin_flags="A" 
                    AND u.status IN ("A", "R") 
                    AND ug.group_id ='.$this->da->escapeInt($groupId);
        return $this->retrieve($sql);
    }

    /**
     * Remove users from a given project
     *
     * @param Integer $groupId
     *
     * @return Boolean
     */
    function removeProjectMembers($groupId) {
        $groupId = $this->da->escapeInt($groupId);
        $sql     = "DELETE FROM user_group".
                   " WHERE group_id = ".$groupId;
        return $this->update($sql);
    }
}

?>