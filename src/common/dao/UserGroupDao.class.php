<?php
/*
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2009. Xerox Codendi Team.
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
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
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

}


?>