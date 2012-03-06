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

class Tracker_Hierarchy {
    
    private $parents = array();

    private $levelHierarchyTmp = array();
    
    public function addRelationship($parent, $child) {
        if (!array_key_exists($parent, $this->parents)) {
            $this->parents[$parent] = null;
        }
        $this->parents[$child] = $parent;
    }
    
    
    public function getLevel($tracker_id) {
        $this->levelHierarchyTmp = array();
        return $this->getLevelRecursive($tracker_id);
    }
    
    private function getLevelRecursive($tracker_id) {
        if (array_key_exists($tracker_id, $this->parents)) {
            return $this->computeLevel($this->parents[$tracker_id]);
        } else {
            throw new Tracker_Hierarchy_NotInHierarchyException();
        }
    }
    
    private function computeLevel($tracker_id) {
        if (is_null($tracker_id)) {
            return 0;
        }
        $this->assertHierarchyIsNotCyclic($tracker_id);
        return $this->getLevelRecursive($tracker_id) + 1;
    }
    
    private function assertHierarchyIsNotCyclic($tracker_id) {
        if (in_array($tracker_id, $this->levelHierarchyTmp)) {
            throw new Tracker_Hierarchy_CyclicHierarchyException();
        }
        $this->levelHierarchyTmp[] = $tracker_id;
    }
}
?>
