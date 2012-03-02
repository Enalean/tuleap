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

class Tracker_Hierarchy {
    
    private $relationships=array();
    
    public function __construct(array $relationships) {
        $this->relationships = array();
        foreach ($relationships as $parent_child) {
            $this->addRelationship($parent_child[0], $parent_child[1]);
        }
    }
    
    private function addRelationship($parent, $child) {
        $this->relationships = array_merge_recursive(
            $this->relationships, 
            array(
                "_$parent" => array('parent'=> array(),        'children' => array($child)),
                "_$child"  => array('parent'=> array($parent), 'children' => array()),
            )
        );
    }
    
    
    public function getLevel($tracker_id) {
        if (isset($this->relationships["_$tracker_id"])) {
            $parents = $this->relationships["_$tracker_id"]['parent'];
            if (!empty($parents)) {
                return $this->getLevel($parents[0]) + 1;
            } else {
                return 0;
            }
        } else {
            $message = 'Tracker not in hierarchy';
            throw new Exception($message);
        }
    }
}
?>
