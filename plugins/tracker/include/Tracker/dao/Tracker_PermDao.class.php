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

require_once('common/dao/include/DataAccessObject.class.php');
class Tracker_PermDao extends DataAccessObject {
    function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_perm';
    }

    function searchByUserIdAndTrackerId($user_id, $tracker_id) {
        $user_id      = $this->da->escapeInt($user_id);
        $tracker_id      = $this->da->escapeInt($tracker_id);
        
        $sql = "SELECT *
                FROM $this->table_name
                WHERE user_id = $user_id 
                  AND tracker_id = $tracker_id";
        return $this->retrieve($sql);
    }
    
    function updateUser($user_id, $perm_level, $tracker_id) {
        $user_id        = $this->da->escapeInt($user_id);
        $perm_level     = $this->da->escapeInt($perm_level);
        $tracker_id     = $this->da->escapeInt($tracker_id);

        $sql = "UPDATE $this->table_name 
                SET perm_level = $perm_level
                WHERE user_id = $user_id
                  AND tracker_id = $tracker_id";
        return $this->update($sql);
    }
    
    function createUser($user_id, $perm_level, $tracker_id) {
        $user_id = $this->da->escapeInt($user_id);
        $perm_level = $this->da->escapeInt($perm_level);
        $tracker_id      = $this->da->escapeInt($tracker_id);
        
        $sql = "INSERT INTO $this->table_name (tracker_id, user_id, perm_level)
                VALUES ($tracker_id, $user_id, $perm_level)";

        return $this->update($sql);
    }
    
    function deleteUser($user_id, $tracker_id) {
        $user_id    = $this->da->escapeInt($user_id);
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "DELETE FROM $this->table_name WHERE user_id = $user_id AND tracker_id = $tracker_id ";
        return $this->update($sql);
    }
    
    function searchAccessPermissionsByTrackerId($tracker_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        
        $sql="SELECT *
              FROM permissions 
              WHERE (permission_type LIKE 'PLUGIN_TRACKER_ACCESS%'
                    OR permission_type = '".Tracker::PERMISSION_ADMIN."')
                    AND object_id='$tracker_id'
              ORDER BY ugroup_id";
        return $this->retrieve($sql);
    }

    function searchAccessPermissionsByFieldId($field_id) {
        $field_id = $this->da->escapeInt($field_id);

        $sql="SELECT *
              FROM permissions
              WHERE permission_type LIKE 'PLUGIN_TRACKER_FIELD%'
                    AND object_id='$field_id'
              ORDER BY ugroup_id";
        return $this->retrieve($sql);
    }

}
?>
