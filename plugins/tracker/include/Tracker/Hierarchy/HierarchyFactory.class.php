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

require_once 'Hierarchy.class.php';
require_once 'Dao.class.php';

class Tracker_HierarchyFactory {
    
    protected $hierarchy_dao;
    
    /**
     * Used to instanciate some related trackers according to their hierarchy,
     * without the need of a tree structure (e.g. retrieve direct children of a
     * given Tracker).
     * 
     * @var TrackerFactory
     */
    private $tracker_factory;
    
    public function __construct(Tracker_Hierarchy_Dao $hierarchy_dao, TrackerFactory $tracker_factory) {
        $this->hierarchy_dao   = $hierarchy_dao;
        $this->tracker_factory = $tracker_factory;
    }
    
    /**
     * Returns a new instance of Tracker_HierarchyFactory.
     * 
     * We should usually prefer dependency injection over static methods, but
     * there are some cases in Tuleap legacy code where injection would require
     * a lot of refactoring (e.g. Tracker/FormElement).
     */
    public static function build() {
        return new Tracker_HierarchyFactory(new Tracker_Hierarchy_Dao(), TrackerFactory::instance());
    }
    
    public function getChildren($tracker_id) {
        $children = array();
        
        foreach($this->hierarchy_dao->searchChildTrackerIds($tracker_id) as $row) {
            $children[] = $this->tracker_factory->getTrackerById($row['id']);
        }
        
        return $children;
    }
    
    public function getHierarchy($tracker_ids = array()) {
        $hierarchy             = new Tracker_Hierarchy();
        $search_tracker_ids    = $tracker_ids;
        $processed_tracker_ids = array();
        while (!empty($search_tracker_ids)) {
            $this->getHierarchyFromTrackers($hierarchy, $search_tracker_ids, $processed_tracker_ids);
        }
        return $hierarchy;
    }
    
    private function getHierarchyFromTrackers(Tracker_Hierarchy $hierarchy, &$search_tracker_ids, &$processed_tracker_ids) {
        $processed_tracker_ids   = array_merge($processed_tracker_ids, $search_tracker_ids);
        $added_relationships_ids = $this->addRelationships($hierarchy, $search_tracker_ids);
        $search_tracker_ids      = array_values(array_diff($added_relationships_ids, $processed_tracker_ids));
    }
    
    private function addRelationships(Tracker_Hierarchy $hierarchy, $search_tracker_ids) {
        $hierarchy_dar     = $this->hierarchy_dao->searchTrackerHierarchy($search_tracker_ids);
        
        $relationships_ids = array();
        foreach ($hierarchy_dar as $row) {
            $this->addRelationshipAndStack($hierarchy, $row['parent_id'], $row['child_id'], $relationships_ids);
        }
        
        return $relationships_ids;
    }
    
    private function addRelationshipAndStack($hierarchy, $parent_id, $child_id, &$stack) {
        $hierarchy->addRelationship($parent_id, $child_id);
        
        $stack[] = $parent_id;
        $stack[] = $child_id;
    }
}

?>
