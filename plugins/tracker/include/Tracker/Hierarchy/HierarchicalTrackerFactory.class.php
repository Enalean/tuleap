<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\Tracker\Hierarchy\HierarchyDAO;

class Tracker_Hierarchy_HierarchicalTrackerFactory
{

    public function __construct(TrackerFactory $tracker_factory, HierarchyDAO $dao)
    {
        $this->tracker_factory = $tracker_factory;
        $this->dao             = $dao;
    }

    /**
     * Holds an instance of the class
     * @var Tracker_Hierarchy_HierarchicalTrackerFactory
     */
    private static $instance;

    /**
     * Allows to inject a fake factory for test. DO NOT USE IT IN PRODUCTION!
     *
     * @param Tracker_Hierarchy_HierarchicalTrackerFactory $factory
     */
    public static function setInstance(Tracker_Hierarchy_HierarchicalTrackerFactory $instance)
    {
        self::$instance = $instance;
    }

    /**
     * Allows clear factory instance for test. DO NOT USE IT IN PRODUCTION!
     */
    public static function clearInstance()
    {
        self::$instance = null;
    }

    /**
     * The singleton method
     *
     * @return Tracker_Hierarchy_HierarchicalTrackerFactory
     */
    public static function instance()
    {
        if (! self::$instance) {
            self::$instance = new Tracker_Hierarchy_HierarchicalTrackerFactory(TrackerFactory::instance(), new HierarchyDAO());
        }
        return self::$instance;
    }

    /**
     * @return Tracker_Hierarchy_HierarchicalTracker
     */
    public function getWithChildren(Tracker $tracker)
    {
        return new Tracker_Hierarchy_HierarchicalTracker($tracker, $this->getChildren($tracker));
    }

    /**
     * @return array of Tracker
     */
    public function getChildren(Tracker $tracker)
    {
        $children_ids = $this->dao->getChildren($tracker->getId());
        $children     = [];

        foreach ($children_ids as $child_id) {
            $children[] = $this->tracker_factory->getTrackerById($child_id);
        }

        return $children;
    }

    /**
     * @return Array of Tracker
     */
    public function getPossibleChildren(Tracker_Hierarchy_HierarchicalTracker $tracker)
    {
        $project_trackers  = $this->getProjectTrackers($tracker->getProject());
        $ids_to_remove     = $this->dao->searchAncestorIds($tracker->getId());
        $ids_to_remove[]   = $tracker->getId();

        $project_trackers = $this->removeIdsFromTrackerList($project_trackers, $ids_to_remove);

        return $project_trackers;
    }

    private function getProjectTrackers(Project $project)
    {
        return $this->tracker_factory->getTrackersByGroupId($project->getID());
    }

    private function removeIdsFromTrackerList($tracker_list, $tracker_ids_to_remove)
    {
        $array_with_keys_to_remove = array_combine($tracker_ids_to_remove, range(0, count($tracker_ids_to_remove) - 1));
        return array_diff_key($tracker_list, $array_with_keys_to_remove);
    }

    /**
     * @return TreeNode
     */
    public function getHierarchy(Tracker $tracker)
    {
        $project_trackers = $this->getProjectTrackers($tracker->getProject());
        $parent_child_dar = $this->dao->searchParentChildAssociations($tracker->getGroupId());
        $children_map     = $this->getChildrenMapFromDar($parent_child_dar, $project_trackers);

        $root = new TreeNode();
        $root->setId('root');

        $this->buildHierarchyChildrenOf($root, $children_map, $project_trackers, $tracker);

        return $root;
    }

    private function buildHierarchyChildrenOf($parent_node, $children_map, $project_trackers, $current_tracker)
    {
        $children_ids = $children_map[$parent_node->getId()];

        foreach ($children_ids as $child_id) {
            $tracker = $project_trackers[$child_id];
            $node    = $this->makeNodeFor($tracker, $current_tracker);

            $this->buildHierarchyChildrenOf($node, $children_map, $project_trackers, $current_tracker);
            $parent_node->addChild($node);
        }
    }

    public function getChildrenMapFromDar(array $hierarchy_rows, $project_trackers)
    {
        $children  = array();
        $hierarchy_map = array();
        foreach ($hierarchy_rows as $relationship) {
            $parent_id = $relationship['parent_id'];
            $child_id  = $relationship['child_id'];
            $children[] = $child_id;

            if (!isset($hierarchy_map[$child_id])) {
                $hierarchy_map[$child_id] = array();
            }
            if (!isset($hierarchy_map[$parent_id])) {
                $hierarchy_map[$parent_id] = array($child_id);
            } else {
                $hierarchy_map[$parent_id][] = $child_id;
            }
        }

        $hierarchy_map['root'] = array_values(array_diff(array_keys($hierarchy_map), $children));

        $unhierarchized_root_tracker_ids = array_diff(array_keys($project_trackers), array_keys($hierarchy_map));
        foreach ($unhierarchized_root_tracker_ids as $tracker_id) {
            $hierarchy_map[$tracker_id] = array();
            $hierarchy_map['root'][]    = $tracker_id;
        }

        return $hierarchy_map;
    }

    private function makeNodeFor($tracker, $current_tracker)
    {
        $current_class = '';

        if ($tracker->getId() == $current_tracker->getId()) {
            $current_class = 'tracker-hierarchy-current';
        }

        $node = new TreeNode(array('name'          => $tracker->getName(),
                                   'id'            => $tracker->getId(),
                                   'current_class' => $current_class));
        $node->setId($tracker->getId());

        return $node;
    }

    public function getRootTrackerId($hierarchy_dar, $current_tracker_id)
    {
        foreach ($hierarchy_dar as $child) {
            if ($child['child_id'] == $current_tracker_id) {
                return $this->getRootTrackerId($hierarchy_dar, $child['parent_id']);
            }
        }
        return $current_tracker_id;
    }
}
