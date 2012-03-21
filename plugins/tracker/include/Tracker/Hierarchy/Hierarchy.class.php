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

require_once 'NotInHierarchyException.class.php';
require_once 'CyclicHierarchyException.class.php';

/**
 * Store relationship between tracker based on their ids.
 * 
 * This allows to compute the level of a tracker in a given hierarchy. 
 */
class Tracker_Hierarchy {
    
    private $parents = array();
    
    /**
     * @param int $parent_id The id of the parent in the relatonship
     * @param int $child_id  The id of the parent in the relatonship
     */
    public function addRelationship($parent_id, $child_id) {
        if (!array_key_exists($parent_id, $this->parents)) {
            $this->parents[$parent_id] = null;
        }
        $this->parents[$child_id] = $parent_id;
    }
    
    /**
     * @throws Tracker_Hierarchy_NotInHierarchyException
     * @throws Tracker_Hierarchy_CyclicHierarchyException
     *
     * @return int the level of the tracker accordingly to the hierarchy
     */
    public function getLevel($tracker_id) {
        $callstack = array();
        return $this->getLevelRecursive($tracker_id, $callstack);
    }
    
    /**
     * @return bool
     */
    public function isChild($parent_id, $child_id) {
        return isset($this->parents[$child_id]) && $this->parents[$child_id] == $parent_id;
    }
    
    private function getLevelRecursive($tracker_id, array &$callstack) {
        if (array_key_exists($tracker_id, $this->parents)) {
            return $this->computeLevel($this->parents[$tracker_id], $callstack);
        } else {
            throw new Tracker_Hierarchy_NotInHierarchyException();
        }
    }
    
    private function computeLevel($tracker_id, array &$callstack) {
        if (is_null($tracker_id)) {
            return 0;
        }
        $this->assertHierarchyIsNotCyclic($tracker_id, $callstack);
        return $this->getLevelRecursive($tracker_id, $callstack) + 1;
    }
    
    private function assertHierarchyIsNotCyclic($tracker_id, array &$callstack) {
        if (in_array($tracker_id, $callstack)) {
            throw new Tracker_Hierarchy_CyclicHierarchyException();
        }
        $callstack[] = $tracker_id;
    }
}
?>
