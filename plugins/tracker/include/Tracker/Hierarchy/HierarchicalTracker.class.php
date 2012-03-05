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

class Tracker_Hierarchy_HierarchicalTracker {

    /**
     * @var Tracker
     */
    private $parent;
    
    /**
     * @var Array of Tracker
     */
    private $children;
    
    public function __construct(Tracker $parent, array $children) {
        $this->parent   = $parent;
        $this->children = $children;
    }
    
    public function getId() {
        return $this->parent->getId();
    }
    
    public function getProject() {
        return $this->parent->getProject();
    }
    
    public function getChildren() {
        return $this->children;
    }
    
    public function hasChild(Tracker $tracker) {
        return in_array($tracker, $this->children);
    }
}
?>
