<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkFieldValueDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildLinkRetriever;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;

class Tracker_HierarchyFactory
{
    private static $_instance;

    /**
     * @var array of tracker id (children of a tracker)
     */
    private $cache_children_of_tracker = [];

    /**
     * @var array
     */
    private $cache_ancestors = [];

    /**
     * @var HierarchyDAO
     */
    private $hierarchy_dao;

    /**
     * Used to instanciate some related trackers according to their hierarchy,
     * without the need of a tree structure (e.g. retrieve direct children of a
     * given Tracker).
     *
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var TypeIsChildLinkRetriever
     */
    private $child_link_retriever;

    public function __construct(
        HierarchyDAO $hierarchy_dao,
        TrackerFactory $tracker_factory,
        Tracker_ArtifactFactory $artifact_factory,
        TypeIsChildLinkRetriever $child_link_retriever,
    ) {
        $this->hierarchy_dao        = $hierarchy_dao;
        $this->tracker_factory      = $tracker_factory;
        $this->artifact_factory     = $artifact_factory;
        $this->child_link_retriever = $child_link_retriever;
    }

    /**
     * Returns an instance of Tracker_HierarchyFactory (creating it when needed).
     *
     * We should usually prefer dependency injection over static methods, but
     * there are some cases in Tuleap legacy code where injection would require
     * a lot of refactoring (e.g. Tracker/FormElement).
     *
     * @return Tracker_HierarchyFactory
     */
    public static function instance()
    {
        if (! self::$_instance) {
            self::$_instance = new Tracker_HierarchyFactory(
                new HierarchyDAO(),
                TrackerFactory::instance(),
                Tracker_ArtifactFactory::instance(),
                new TypeIsChildLinkRetriever(
                    Tracker_ArtifactFactory::instance(),
                    new ArtifactLinkFieldValueDao()
                )
            );
        }
        return self::$_instance;
    }

    public static function setInstance(Tracker_HierarchyFactory $instance)
    {
        self::$_instance = $instance;
    }

    public static function clearInstance()
    {
        self::$_instance = null;
    }

    /**
     *
     * @param int $tracker_id
     * @return Tracker[]
     */
    public function getChildren($tracker_id): array
    {
        if (! isset($this->cache_children_of_tracker[$tracker_id])) {
            $this->cache_children_of_tracker[$tracker_id] = [];
            foreach ($this->hierarchy_dao->searchChildTrackerIds($tracker_id) as $row) {
                $tracker = $this->tracker_factory->getTrackerById($row['id']);
                if ($tracker !== null && ! $tracker->isDeleted()) {
                    $this->cache_children_of_tracker[$tracker_id][] = $tracker;
                }
            }
        }
        return $this->cache_children_of_tracker[$tracker_id];
    }

    /**
     * Return the whole hierarchy (parents and descendants) that involve the given trackers
     *
     * @param array $tracker_ids
     *
     * @return \Tracker_Hierarchy
     */
    public function getHierarchy($tracker_ids = [])
    {
        $hierarchy             = new Tracker_Hierarchy();
        $search_tracker_ids    = $tracker_ids;
        $processed_tracker_ids = [];
        while (! empty($search_tracker_ids)) {
            $this->getHierarchyFromTrackers($hierarchy, $search_tracker_ids, $processed_tracker_ids);
        }
        return $this->fixSingleHierarchy($tracker_ids, $hierarchy);
    }

    /**
     * If no other trackers were found in hierarchy, returns the tracker alone in hierarchy
     *
     *
     * @return \Tracker_Hierarchy
     */
    private function fixSingleHierarchy(array $tracker_ids, Tracker_Hierarchy $hierarchy)
    {
        if (count($tracker_ids) == 1 && ! $hierarchy->flatten()) {
            $hierarchy->addRelationship($tracker_ids[0], 0);
        }
        return $hierarchy;
    }

    /**
     * @return Tracker|null
     */
    public function getParent(Tracker $tracker)
    {
        $hierarchy         = $this->getHierarchy([$tracker->getId()]);
        $parent_tracker_id = $hierarchy->getParent($tracker->getId());
        if ($parent_tracker_id === null) {
            return null;
        }
        return $this->tracker_factory->getTrackerById($parent_tracker_id);
    }

    /**
     * Epic
     * `-- Story
     *     `-- Task
     * getAllParents(Task) -> ['Story', 'Epic']
     *
     * @return Tracker[]
     */
    public function getAllParents(Tracker $tracker)
    {
        $hierarchy         = $this->getHierarchy([$tracker->getId()]);
        $parent_tracker_id = $hierarchy->getParent($tracker->getId());
        $stack             = [];
        while (($parent_tracker_id !== null) && ($parent_tracker = $this->tracker_factory->getTrackerById($parent_tracker_id))) {
            $stack[]           = $parent_tracker;
            $parent_tracker_id = $hierarchy->getParent($parent_tracker->getId());
        }
        return $stack;
    }

    /**
     * Epic
     * `-- Story
     *     `-- Task
     * getUpwardsHierarchyForTracker(Task) -> ['Task tracker ID', 'Story tracker ID', 'Epic tracker ID']
     *
     * @return Int[] array of IDs
     */
    public function getUpwardsHierarchyForTracker($tracker_id)
    {
        $hierarchy         = $this->getHierarchy([$tracker_id]);
        $parent_tracker_id = $hierarchy->getParent($tracker_id);
        $stack             = [$tracker_id];
        while ($parent_tracker_id) {
            $stack[]           = $parent_tracker_id;
            $parent_tracker_id = $hierarchy->getParent($parent_tracker_id);
        }
        return $stack;
    }

    /**
     * Return the parent artifact
     *
     *
     * @return null| Artifact
     */
    public function getParentArtifact(PFUser $user, Artifact $child)
    {
        $parents = [];
        if ($child->getTracker()->isProjectAllowedToUseType() === true) {
            $parents = $this->child_link_retriever->getDirectParents($child);
        } else {
            $rows = $this->hierarchy_dao->getParentsInHierarchy($child->getId());
            foreach ($rows as $row) {
                $parents[] = $this->artifact_factory->getInstanceFromRow($row);
            }
        }

        if (count($parents) > 1) {
            $warning = sprintf(dgettext('tuleap-tracker', 'Artifact %1$s has more than one parent: %2$s'), $this->getParentTitle($child), $this->getParentsList($parents));
            $GLOBALS['Response']->addFeedback('warning', $warning, CODENDI_PURIFIER_LIGHT);
        }
        if (isset($parents[0])) {
            return $parents[0];
        }
        return null;
    }

    private function getParentsList(array $parents)
    {
        return implode(', ', array_map([$this, 'getParentTitle'], $parents));
    }

    private function getParentTitle(Artifact $artifact)
    {
        return '"' . $artifact->getTitle() . ' (' . $artifact->fetchXRefLink() . ')"';
    }

    /**
     * Return all hierarchy of parents of an artifact (from direct parent to oldest ancestor)
     *
     * Epic
     * `-- Story
     *     `-- Task
     * getAllAncestors(User, Task) -> ['Story', 'Epic']
     *
     * @param array $stack (purly internal for recursion, should not be used
     *
     * @return Array of Tracker_Artifact
     */
    public function getAllAncestors(PFUser $user, Artifact $child, array &$stack = [])
    {
        if (! isset($this->cache_ancestors[$user->getId()][$child->getId()])) {
            $parent = $this->getParentArtifact($user, $child);
            if ($parent === null || $parent->getId() == $child->getId() || isset($stack[$parent->getId()])) {
                $this->cache_ancestors[$user->getId()][$child->getId()] = [];
            } else {
                $stack[$parent->getId()]                                = true;
                $this->cache_ancestors[$user->getId()][$child->getId()] = array_merge([$parent], $this->getAllAncestors($user, $parent, $stack));
            }
        }
        return $this->cache_ancestors[$user->getId()][$child->getId()];
    }

    /**
     * Duplicate a tracker hierarchy
     *
     * @param Array   $tracker_mapping the trackers mapping during project creation based on a template
     */
    public function duplicate($tracker_mapping)
    {
        $search_tracker_ids = array_keys($tracker_mapping);
        $hierarchy_dar      = $this->hierarchy_dao->searchTrackerHierarchy($search_tracker_ids);

        foreach ($hierarchy_dar as $row) {
            $this->hierarchy_dao->duplicate($row['parent_id'], $row['child_id'], $tracker_mapping);
        }
    }

    private function getHierarchyFromTrackers(Tracker_Hierarchy $hierarchy, &$search_tracker_ids, &$processed_tracker_ids)
    {
        $processed_tracker_ids   = array_merge($processed_tracker_ids, $search_tracker_ids);
        $added_relationships_ids = $this->addRelationships($hierarchy, $search_tracker_ids);
        $search_tracker_ids      = array_values(array_diff($added_relationships_ids, $processed_tracker_ids));
    }

    private function addRelationships(Tracker_Hierarchy $hierarchy, $search_tracker_ids): array
    {
        $hierarchy_dar = $this->hierarchy_dao->searchTrackerHierarchy($search_tracker_ids);

        $relationships_ids = [];
        foreach ($hierarchy_dar as $row) {
            $this->addRelationshipAndStack($hierarchy, $row['parent_id'], $row['child_id'], $relationships_ids);
        }

        return $relationships_ids;
    }

    private function addRelationshipAndStack($hierarchy, $parent_id, $child_id, &$stack)
    {
        $hierarchy->addRelationship($parent_id, $child_id);

        $stack[] = $parent_id;
        $stack[] = $child_id;
    }
}
