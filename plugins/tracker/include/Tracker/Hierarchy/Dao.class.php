<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/dao/include/DataAccessObject.class.php';

class Tracker_Hierarchy_Dao extends DataAccessObject {
    
    public function updateChildren($parent_id, array $child_ids) {
        $parent_id = $this->da->escapeInt($parent_id);
        $sql = "DELETE FROM tracker_hierarchy WHERE parent_id = $parent_id";
        $this->update($sql);
        
        foreach($child_ids as $child_id) {
            $child_id = $this->da->escapeInt($child_id);
            $insert_values[] = "($parent_id, $child_id)";
        }
        $sql = "INSERT INTO tracker_hierarchy(parent_id, child_id) VALUES ".implode(',', $insert_values);
        $this->update($sql);
    }

    public function getChildren($tracker_id) {
        $sql = "SELECT child_id FROM tracker_hierarchy WHERE parent_id = $tracker_id";
        return $this->retrieve($sql);
    }
    
    public function searchTrackerHierarchy(array $tracker_ids) {
        $tracker_ids = array_map(array($this->da, 'escapeInt'), $tracker_ids);
        $tracker_ids = implode(',', $tracker_ids);
        $sql = "SELECT parent_id, child_id
                FROM tracker_hierarchy 
                WHERE parent_id IN ($tracker_ids) 
                   OR child_id  IN ($tracker_ids)";
        return $this->retrieve($sql);
    }
    
    public function getHierarchy($tracker_id) {
//        $sql = "SELECT parent_id, tracker.*
//                FROM tracker_hierarchy, tracker
//                  ON (tracker.id = parent_id)
//                WHERE group_id = $group_id";
//        //
    }
}

?>
