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
        $this->deleteAllChildren($parent_id);
        
        // TODO:
        // $ancestor_ids = $this->getAncestorIds($parent_id);
        // $child_ids    = array_diff($child_ids, $ancestor_ids);
        
        foreach($child_ids as $child_id) {
            $child_id = $this->da->escapeInt($child_id);
            $insert_values[] = "($parent_id, $child_id)";
        }
        $sql = "REPLACE INTO tracker_hierarchy(parent_id, child_id) VALUES ".implode(',', $insert_values);
        $this->update($sql);
    }
    
    public function searchAncestorIds($tracker_id) {
        $ancestor_ids = array();
        while($tracker_id = $this->searchAncestorId($tracker_id)) {
            $ancestor_ids[] = $tracker_id;
        }
        return $ancestor_ids;
    }
    
    private function searchAncestorId($tracker_id) {
        $sql = "SELECT parent_id FROM tracker_hierarchy WHERE child_id= " . $tracker_id. " LIMIT 1";
        $dar = $this->retrieve($sql);
        $result = array('parent_id'=>null);
        foreach($dar as $result) {}
        return $result['parent_id'];
    }
    
    public function deleteAllChildren($parent_id) {
        $parent_id = $this->da->escapeInt($parent_id);
        $sql = "DELETE FROM tracker_hierarchy WHERE parent_id = $parent_id";
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
    
    public function searchParentChildAssociations($group_id) {
        $sql = "SELECT h.*
                FROM       tracker_hierarchy AS h
                INNER JOIN tracker           AS t ON (t.id = h.parent_id)
                WHERE t.group_id = $group_id";
        
        return $this->retrieve($sql);
    }
    
    public function duplicate($parent_id, $child_id, $tracker_mapping){
        $parent_id = $this->da->escapeInt($tracker_mapping[$parent_id]);
        $child_id  = $this->da->escapeInt($tracker_mapping[$child_id]);
        
        $sql = "INSERT INTO tracker_hierarchy (parent_id, child_id)
                VALUES ($parent_id, $child_id)";
        
        return $this->update($sql);
    }
}

?>
