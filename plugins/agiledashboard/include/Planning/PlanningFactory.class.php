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

require_once dirname(__FILE__) .'/../../../tracker/include/Tracker/TrackerFactory.class.php';

class PlanningFactory {

    /**
     * @var PlanningDao
     */
    private $dao;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function __construct(PlanningDao $dao, TrackerFactory $tracker_factory) {
        $this->dao             = $dao;
        $this->tracker_factory = $tracker_factory;
    }

    /**
     * @return PlanningFactory
     */
    public static function build() {
        return new PlanningFactory(new PlanningDao(), TrackerFactory::instance());
    }

    /**
     * Duplicate plannings for some previously duplicated trackers.
     *
     * @param int    $group_id         The id of the project where plannings should be created.
     * @param array  $tracker_mapping  An array mapping source tracker ids to destination tracker ids.
     */
    public function duplicatePlannings($group_id, $tracker_mapping) {
        if (! $tracker_mapping) {return;}

        $planning_rows = $this->dao->searchByPlanningTrackerIds(array_keys($tracker_mapping));

        foreach($planning_rows as $row) {
            if(isset($tracker_mapping[$row['backlog_tracker_id']]) && 
                    isset($tracker_mapping[$row['backlog_tracker_id']])) {
                $row['backlog_tracker_id']  = $tracker_mapping[$row['backlog_tracker_id']];
                $row['planning_tracker_id'] = $tracker_mapping[$row['planning_tracker_id']];
                
                $this->dao->createPlanning($group_id, PlanningParameters::fromArray($row));
            }
        }
    }

    /**
     * $tracker_mapping = array(1 => 4,
     *                          2 => 5,
     *                          3 => 6);
     *
     * $factory->filterByKeys($tracker_mapping, array(1, 3))
     *
     * => array(1 => 4,
     *          3 => 6)
     *
     * @param array $array The array to filter.
     * @param array $keys  The keys used for filtering.
     *
     * @return array
     */
    private function filterByKeys(array $array, array $keys) {
        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * Get a list of planning defined in a group_id
     *
     * @param User $user     The user who will see the planning
     * @param int  $group_id
     *
     * @return array of Planning
     */
    public function getPlannings(User $user, $group_id) {
        $plannings = array();
        foreach ($this->dao->searchPlannings($group_id) as $row) {
            $tracker = $this->tracker_factory->getTrackerById($row['planning_tracker_id']);
            if ($tracker && $tracker->userCanView($user)) {
                $planning = new Planning($row['id'], $row['name'], $row['group_id'], $row['backlog_title'], $row['plan_title'], null, $row['planning_tracker_id']);
                $plannings[] = $planning;
            }
        }
        if ($plannings) {
            $this->sortPlanningsAccordinglyToHierarchy($plannings);
        }
        return $plannings;
    }

    /**
     * Get a list of planning short access defined in a group_id
     *
     * @param User $user     The user who will see the planning
     * @param int  $group_id
     *
     * @return array of Planning_ShortAccess
     */
    public function getPlanningsShortAccess(User $user, $group_id, Planning_MilestoneFactory $milestone_factory) {
        $plannings    = $this->getPlannings($user, $group_id);
        $short_access = array();
        foreach ($plannings as $planning) {
            $short_access[] = new Planning_ShortAccess($planning, $user, $milestone_factory);
        }
        if (!empty($short_access)) {
            end($short_access)->setIsLatest();
        }
        return $short_access;
    }

    private function sortPlanningsAccordinglyToHierarchy(array &$plannings) {
        $tracker_ids = array_map(array($this, 'getPlanningTrackerId'), $plannings);
        $hierarchy   = $this->tracker_factory->getHierarchy($tracker_ids);
        $this->tmp_tracker_ids_to_sort_plannings = $hierarchy->sortTrackerIds($tracker_ids);
        usort($plannings, array($this, 'cmpPlanningTrackerIds'));
    }

    private function getPlanningTrackerId(Planning $planning) {
        return $planning->getPlanningTrackerId();
    }

    private function cmpPlanningTrackerIds($a, $b) {
        return strcmp(
            array_search($a->getPlanningTrackerId(), $this->tmp_tracker_ids_to_sort_plannings),
            array_search($b->getPlanningTrackerId(), $this->tmp_tracker_ids_to_sort_plannings)
        );
    }

    /**
     * Get a planning
     *
     * @param int $group_id
     *
     * @return Planning
     */
    public function getPlanning($planning_id) {
        $planning =  $this->dao->searchById($planning_id)->getRow();
        if ($planning) {
            $backlog_tracker_id = $this->getBacklogTrackerId($planning_id);
            return new Planning($planning_id,
                                $planning['name'],
                                $planning['group_id'],
                                $planning['backlog_title'],
                                $planning['plan_title'],
                                $backlog_tracker_id,
                                $planning['planning_tracker_id']);
        }
        return null;
    }

    /**
     * Returns the planning that uses the given tracker as milestone's source
     *
     * Example:
     * - Given I pass Release tracker as parameter
     * - Then I should get the Release planning (for instance Epic -> Release)
     *
     * @param Tracker $planning_tracker
     *
     * @return Planning
     */
    public function getPlanningByPlanningTracker(Tracker $planning_tracker) {
        $planning = $this->dao->searchByPlanningTrackerId($planning_tracker->getId())->getRow();

        if($planning) {
            $p = new Planning($planning['id'],
                              $planning['name'],
                              $planning['group_id'],
                              $planning['backlog_title'],
                              $planning['plan_title'],
                              null,
                              $planning['planning_tracker_id']);
            $p->setPlanningTracker($this->getPlanningTracker($p));
            $p->setBacklogTracker($this->getBacklogTracker($p));
            return $p;
        }
    }

    /**
     * Returns all the Planning that use given tracker as backlog tracker
     *
     * Given:
     *   Epic  -> Product
     *   Epic  -> Release
     *   Story -> Sprint
     * When getPlanningsByBacklogTracker(Epic) -> [Product, Release]
     * When getPlanningsByBacklogTracker(Story) -> [Sprint]
     *
     * @param Tracker $backlog_tracker
     *
     * @return Planning
     */
    public function getPlanningsByBacklogTracker(Tracker $backlog_tracker) {
        $plannings = array();
        foreach ($this->dao->searchByBacklogTrackerId($backlog_tracker->getId()) as $planning) {
            $p = new Planning(
                $planning['id'],
                $planning['name'],
                $planning['group_id'],
                $planning['backlog_title'],
                $planning['plan_title'],
                $backlog_tracker->getId(),
                $planning['planning_tracker_id']
            );
            $p->setBacklogTracker($backlog_tracker);
            $p->setPlanningTracker($this->getPlanningTracker($p));
            $plannings[] = $p;
        }
        return $plannings;
    }

    public function getPlanningWithTrackers($planning_id) {
        $planning = $this->getPlanning($planning_id);

        if ($planning) {
            // TODO: do not use setters...
            $planning->setPlanningTracker($this->getPlanningTracker($planning));
            $planning->setBacklogTracker($this->getBacklogTracker($planning));
        }

        return $planning;
    }

    /**
     * Build a new planning in a project
     *
     * @param int $group_id
     *
     * @return Planning
     */
    public function buildNewPlanning($group_id) {
        return new Planning(null, null, $group_id, 'Release Backlog', 'Sprint Backlog');
    }

    /**
     * Get a list of tracker ids defined as backlog for a planning
     *
     * @param int $planning_id
     *
     * @return array of tracker id
     */
    public function getBacklogTrackerId($planning_id) {
        $row = $this->dao->searchBacklogTrackerById($planning_id);
        return $row['tracker_id'];
    }

    /**
     * @return Tracker
     */
    private function getPlanningTracker(Planning $planning) {
        return $this->tracker_factory->getTrackerById($planning->getPlanningTrackerId());
    }

    /**
     * Get a list of trackers defined as backlog for a planning
     *
     * @param Planning $planning
     *
     * @return array of Tracker
     */
    private function getBacklogTracker(Planning $planning) {
        $planning_id     = $planning->getId();
        $row             = $this->dao->searchBacklogTrackerById($planning_id);
        $backlog_tracker = $this->tracker_factory->getTrackerById($row['tracker_id']);

        return $backlog_tracker;
    }

    public function getPlanningTrackerIdsByGroupId($group_id) {
        return $this->dao->searchPlanningTrackerIdsByGroupId($group_id);
    }

    /**
     * Create a new planning
     *
     * @param int $group_id
     * @param PlanningParameters $planning_parameters
     *
     * @return array of Planning
     */
    public function createPlanning($group_id, PlanningParameters $planning_parameters) {
        return $this->dao->createPlanning($group_id, $planning_parameters);
    }

    /**
     * Update an existing planning
     *
     * @param int $planning_id
     * @param PlanningParameters $planning_parameters
     *
     * @return array of Planning
     */
    public function updatePlanning($planning_id, PlanningParameters $planning_parameters) {
        return $this->dao->updatePlanning($planning_id, $planning_parameters);
    }

    /**
     * Delete planning
     *
     * @param $planning_id the id of the planning
     *
     * @return bool
     */
    public function deletePlanning($planning_id) {
        return $this->dao->deletePlanning($planning_id);
    }

    /**
     * @param int $group_id the project id the trackers to retrieve belong to
     *
     * @return Array of Tracker
     */
    public function getAvailableTrackers($group_id) {
        return array_values($this->tracker_factory->getTrackersByGroupId($group_id));
    }

    /**
     * Retrieve the project trackers that can be used as planning trackers.
     *
     * @param Planning $planning The planning for which we want to know the available trackers.
     *
     * @return Array of Tracker
     */
    public function getAvailablePlanningTrackers(Planning $planning) {
        $planning_trackers = array($planning->getPlanningTracker());

        foreach($this->dao->searchNonPlanningTrackersByGroupId($planning->getGroupId()) as $row) {
            $planning_trackers[] = $this->tracker_factory->getInstanceFromRow($row);
        }

        return $planning_trackers;
    }

    /**
     * @return TrackerFactory
     */
    public function getTrackerFactory() {
        return $this->tracker_factory;
    }


    /**
     * Return the 'Planning' tracker (tracker we should be able to use artifacts to perform search.
     *
     * @param Integer $group_id
     *
     * @return Array of Integer
     */
    public function getPlanningTrackers($group_id, User $user) {
        $trackers = array();
        foreach ($this->getPlannings($user, $group_id) as $planning) {
            $planning   = $this->getPlanning($planning->getId());
            $tracker_id = $planning->getPlanningTrackerId();
            if (!isset($trackers[$tracker_id])) {
                if ($tracker = $this->tracker_factory->getTrackerById($tracker_id)) {
                    $trackers[$tracker_id] = $tracker;
                }
            }
        }
        return $trackers;
    }

}

?>
