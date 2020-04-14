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

class PlanningFactory
{
    /**
     * @var PlanningDao
     */
    private $dao;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var PlanningPermissionsManager
     */
    private $planning_permissions_manager;

    public function __construct(
        PlanningDao $dao,
        TrackerFactory $tracker_factory,
        PlanningPermissionsManager $planning_permissions_manager
    ) {
        $this->dao                          = $dao;
        $this->tracker_factory              = $tracker_factory;
        $this->planning_permissions_manager = $planning_permissions_manager;
    }

    /**
     * @return PlanningFactory
     */
    public static function build()
    {
        return new PlanningFactory(
            new PlanningDao(),
            TrackerFactory::instance(),
            new PlanningPermissionsManager()
        );
    }

    /**
     * Duplicate plannings for some previously duplicated trackers.
     *
     * @param int    $group_id         The id of the project where plannings should be created.
     * @param array  $tracker_mapping  An array mapping source tracker ids to destination tracker ids.
     * @param array  $ugroups_mapping  An array mapping source ugroups and destinations ones.
     */
    public function duplicatePlannings($group_id, $tracker_mapping, array $ugroups_mapping)
    {
        if (! $tracker_mapping) {
            return;
        }

        $planning_rows = $this->dao->searchByPlanningTrackerIds(array_keys($tracker_mapping));

        foreach ($planning_rows as $row) {
            if (isset($tracker_mapping[$row['planning_tracker_id']])) {
                $row['planning_tracker_id'] = $tracker_mapping[$row['planning_tracker_id']];
                $row['backlog_tracker_ids'] = array();
                foreach ($this->dao->searchBacklogTrackersById($row['id']) as $backlog_row) {
                    $row['backlog_tracker_ids'][] = $tracker_mapping[$backlog_row['tracker_id']];
                }

                $inserted_planning_id = $this->dao->createPlanning($group_id, PlanningParameters::fromArray($row));

                $this->duplicatePriorityChangePermission($group_id, $row['id'], $inserted_planning_id, $ugroups_mapping);
            }
        }
    }

    protected function duplicatePriorityChangePermission($group_id, $source_planning_id, $new_planning_id, array $ugroups_mapping)
    {
        $source_planning                       = $this->getPlanning($source_planning_id);
        $priority_change_permission_ugroup_ids = $this->planning_permissions_manager->getGroupIdsWhoHasPermissionOnPlanning(
            $source_planning->getId(),
            $source_planning->getGroupId(),
            PlanningPermissionsManager::PERM_PRIORITY_CHANGE
        );

        if ($priority_change_permission_ugroup_ids) {
            $priority_change_permission_ugroup_ids = $this->replaceOldStaticUgroupsWithTheNewOnes($priority_change_permission_ugroup_ids, $ugroups_mapping);
        }

        if (! empty($priority_change_permission_ugroup_ids)) {
            $this->planning_permissions_manager->savePlanningPermissionForUgroups($new_planning_id, $group_id, PlanningPermissionsManager::PERM_PRIORITY_CHANGE, $priority_change_permission_ugroup_ids);
        }
    }

    private function replaceOldStaticUgroupsWithTheNewOnes(array $priority_change_permission_ugroup_ids, array $ugroups_mapping)
    {
        $new_ugroups = array();

        foreach ($priority_change_permission_ugroup_ids as $ugroup) {
            $new_ugroups[] = $this->getUGroupIdToSaveRegardingMappings($ugroup, $ugroups_mapping);
        }

        return $new_ugroups;
    }

    private function getUGroupIdToSaveRegardingMappings($ugroup, $ugroups_mapping)
    {
        if (array_key_exists($ugroup, $ugroups_mapping)) {
            return $ugroups_mapping[$ugroup];
        }

        return $ugroup;
    }

    /**
     * Get a list of planning defined in a group_id
     *
     * @param PFUser $user     The user who will see the planning
     * @param int  $group_id
     *
     * @return Planning[]
     */
    public function getPlannings(PFUser $user, $group_id)
    {
        $plannings = array();
        foreach ($this->dao->searchPlannings($group_id) as $row) {
            $tracker = $this->tracker_factory->getTrackerById($row['planning_tracker_id']);
            if ($tracker && $tracker->userCanView($user)) {
                $plannings[] = $this->getPlanningFromRow($row);
            }
        }
        if ($plannings) {
            $this->sortPlanningsAccordinglyToHierarchy($plannings);
        }
        return $plannings;
    }

    /**
     * Return a planning for a VirtualTopMilestone
     *
     * @param int $group_id
     * @return \Planning
     * @throws Planning_NoPlanningsException
     */
    public function getVirtualTopPlanning(PFUser $user, $group_id)
    {
        $backlog_trackers = array();
        $first_planning   = $this->getRootPlanning($user, $group_id);
        if (! $first_planning) {
            throw new Planning_NoPlanningsException('No Root Plannings Exist');
        }

        $planning_tracker_id  = $first_planning->getPlanningTrackerId();
        $backlog_tracker_ids  = $first_planning->getBacklogTrackersIds();

        $planning_tracker = $this->tracker_factory->getTrackerById($planning_tracker_id);
        if ($planning_tracker === null) {
            throw new RuntimeException('Tracker does not exist');
        }

        foreach ($backlog_tracker_ids as $backlog_tracker_id) {
            $backlog_trackers[] = $this->tracker_factory->getTrackerById($backlog_tracker_id);
        }

        $planning = new Planning(
            null,
            null,
            $group_id,
            null,
            null,
            $backlog_tracker_ids,
            $planning_tracker_id
        );

        $planning
            ->setPlanningTracker($planning_tracker)
            ->setBacklogTrackers($backlog_trackers);

        return $planning;
    }


    /**
     * Return the planning at the top of planning hierarchy
     *
     * Note: if there are several parallel, we only return the fist one
     *
     * @param int $group_id
     *
     * @return Planning | false
     */
    public function getRootPlanning(PFUser $user, $group_id)
    {
        $project_plannings = $this->getOrderedPlanningsWithBacklogTracker($user, $group_id);
        reset($project_plannings);
        return current($project_plannings);
    }

    /**
     * Get all plannings that are children of other plannings but that
     * are not parents themselves
     * @return Planning[]
     */
    public function getLastLevelPlannings(PFUser $user, $group_id)
    {
        $plannings = $this->getPlannings($user, $group_id);

        if ($plannings) {
            $last_level_tracker_ids = $this->getLastLevelPlanningTrackersIds($plannings);

            foreach ($plannings as $key => $planning) {
                if (! in_array($planning->getPlanningTrackerId(), $last_level_tracker_ids)) {
                    unset($plannings[$key]);
                }
            }
        }

        return $plannings;
    }

    /**
     * Get all plannings that are not bottom plannings
     * @return Planning[]
     */
    public function getNonLastLevelPlannings(PFUser $user, $group_id)
    {
        $plannings = $this->getPlannings($user, $group_id);

        if ($plannings) {
            $last_lavel_tracker_ids = $this->getLastLevelPlanningTrackersIds($plannings);

            foreach ($plannings as $key => $planning) {
                if (in_array($planning->getPlanningTrackerId(), $last_lavel_tracker_ids)) {
                    unset($plannings[$key]);
                }
            }

            $this->sortPlanningsAccordinglyToHierarchy($plannings);
        }

        return $plannings;
    }

    /**
     *
     * @param Planning[] $plannings
     * @return array
     */
    private function getLastLevelPlanningTrackersIds($plannings)
    {
        $tracker_ids = array_map(
            static function (Planning $planning) {
                return $planning->getPlanningTrackerId();
            },
            $plannings
        );

        if (count($plannings) > 1) {
            $hierarchy = $this->tracker_factory->getHierarchy($tracker_ids);
            return $hierarchy->getLastLevelTrackerIds();
        }

        return $tracker_ids;
    }

    /**
     * Get a list of planning defined in a group_id
     *
     * @param PFUser $user     The user who will see the planning
     * @param int  $group_id
     * @param PlanningFactory $planning_factory
     *
     * @return Planning[]
     */
    public function getOrderedPlannings(PFUser $user, $group_id)
    {
        $plannings = $this->getPlannings($user, $group_id);

        $this->sortPlanningsAccordinglyToHierarchy($plannings);

        return $plannings;
    }

    /**
     * Get a list of planning defined in a group_id with added backlog trackers
     *
     * @param PFUser $user     The user who will see the planning
     * @param int  $group_id
     * @param PlanningFactory $planning_factory
     *
     * @return Planning[]
     */
    public function getOrderedPlanningsWithBacklogTracker(PFUser $user, $group_id)
    {
        $plannings = $this->getPlannings($user, $group_id);

        foreach ($plannings as $planning) {
            $planning->setBacklogTrackers($this->getBacklogTrackers($planning));
        }

        $this->sortPlanningsAccordinglyToHierarchy($plannings);

        return $plannings;
    }

    private function sortPlanningsAccordinglyToHierarchy(array &$plannings)
    {
        if (! $plannings) {
            return;
        }
        $tracker_ids = array_map(
            static function (Planning $planning) {
                return $planning->getPlanningTrackerId();
            },
            $plannings
        );
        $hierarchy   = $this->tracker_factory->getHierarchy($tracker_ids);
        $tmp_tracker_ids_to_sort_plannings = $hierarchy->sortTrackerIds($tracker_ids);
        usort($plannings, static function (Planning $a, Planning $b) use ($tmp_tracker_ids_to_sort_plannings): int {
            return strcmp(
                array_search($a->getPlanningTrackerId(), $tmp_tracker_ids_to_sort_plannings),
                array_search($b->getPlanningTrackerId(), $tmp_tracker_ids_to_sort_plannings)
            );
        });
    }

    /**
     * Get a planning
     *
     * @param int $planning_id
     *
     * @return Planning
     */
    public function getPlanning($planning_id)
    {
        $planning =  $this->dao->searchById($planning_id)->getRow();
        if (! $planning) {
            return null;
        }

        return $this->getPlanningFromRow($planning);
    }

    /**
     * @param array $row
     *
     * @return Planning
     */
    private function getPlanningFromRow(array $row)
    {
        $planning = new Planning(
            $row['id'],
            $row['name'],
            $row['group_id'],
            $row['backlog_title'],
            $row['plan_title'],
            array(),
            $row['planning_tracker_id']
        );
        $planning->setBacklogTrackers($this->getBacklogTrackers($planning));
        $planning->setPlanningTracker($this->getPlanningTracker($planning));

        return $planning;
    }

    /**
     * Returns the planning that uses the given tracker as milestone's source
     *
     * Example:
     * - Given I pass Release tracker as parameter
     * - Then I should get the Release planning (for instance Epic -> Release)
     *
     *
     * @return Planning|null
     */
    public function getPlanningByPlanningTracker(Tracker $planning_tracker)
    {
        $planning = $this->dao->searchByPlanningTrackerId($planning_tracker->getId())->getRow();

        if ($planning) {
            $p = new Planning(
                $planning['id'],
                $planning['name'],
                $planning['group_id'],
                $planning['backlog_title'],
                $planning['plan_title'],
                array(),
                $planning['planning_tracker_id']
            );
            $p->setPlanningTracker($this->getPlanningTracker($p));
            $p->setBacklogTrackers($this->getBacklogTrackers($p));
            return $p;
        }

        return null;
    }

    public function isTrackerIdUsedInAPlanning($tracker_id)
    {
        $planning = $this->dao->searchByPlanningTrackerId($tracker_id)->getRow();
        if ($planning) {
            return true;
        }
        return false;
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
     *
     * @return Planning
     */
    public function getPlanningsByBacklogTracker(Tracker $backlog_tracker)
    {
        $plannings = array();
        foreach ($this->dao->searchByBacklogTrackerId($backlog_tracker->getId()) as $planning) {
            $p = new Planning(
                $planning['id'],
                $planning['name'],
                $planning['group_id'],
                $planning['backlog_title'],
                $planning['plan_title'],
                array($backlog_tracker->getId()),
                $planning['planning_tracker_id']
            );
            $p->setBacklogTrackers(array($backlog_tracker));
            $p->setPlanningTracker($this->getPlanningTracker($p));
            $plannings[] = $p;
        }
        return $plannings;
    }

    /**
     * Build a new planning in a project
     *
     * @param int $group_id
     *
     * @return Planning
     */
    public function buildNewPlanning($group_id)
    {
        return new Planning(null, null, $group_id, 'Release Backlog', 'Sprint Backlog');
    }

     /**
     * Build a new empty planning
     *
     * @return Planning
     */
    public function buildEmptyPlanning()
    {
        return new Planning(null, null, null, 'Release Backlog', 'Sprint Backlog');
    }

    /**
     * Get a list of tracker ids defined as backlog for a planning
     *
     * @param int $planning_id
     *
     * @return array of tracker id
     */
    public function getBacklogTrackersIds($planning_id)
    {
        $tracker_ids = array();
        $rows = $this->dao->searchBacklogTrackersById($planning_id);
        foreach ($rows as $row) {
            $tracker_ids[] = $row['tracker_id'];
        }

        return $tracker_ids;
    }

    public function getBacklogTrackersIdsIndexedByTrackerId($planning_id)
    {
        $tracker_ids = array();
        $rows = $this->dao->searchBacklogTrackersById($planning_id);
        foreach ($rows as $row) {
            $tracker_ids[$row['tracker_id']] = true;
        }

        return $tracker_ids;
    }

    /**
     * @return Tracker
     */
    private function getPlanningTracker(Planning $planning)
    {
        $tracker = $this->tracker_factory->getTrackerById($planning->getPlanningTrackerId());
        if ($tracker === null) {
            throw new RuntimeException('Tracker does not exist');
        }
        return $tracker;
    }

    /**
     * Get a list of trackers defined as backlog for a planning
     *
     *
     * @return array of Tracker
     */
    private function getBacklogTrackers(Planning $planning)
    {
        $backlog_trackers = array();
        $planning_id      = $planning->getId();
        $rows             = $this->dao->searchBacklogTrackersById($planning_id);

        foreach ($rows as $row) {
            $tracker = $this->tracker_factory->getTrackerById($row['tracker_id']);
            if ($tracker !== null) {
                $backlog_trackers[] = $tracker;
            }
        }

        return $backlog_trackers;
    }

    public function getPlanningTrackerIdsByGroupId($group_id)
    {
        return $this->dao->searchPlanningTrackerIdsByGroupId($group_id);
    }

    public function getBacklogTrackerIdsByGroupId($group_id)
    {
        return $this->dao->searchBacklogTrackerIdsByGroupId($group_id);
    }

    public function isTrackerUsedInBacklog($tracker_id)
    {
        $backlog = $this->dao->searchBacklogItemsByTrackerId($tracker_id)->getRow();

        if ($backlog) {
            return true;
        }
        return false;
    }

    /**
     * Create a new planning
     *
     * @param int $group_id
     */
    public function createPlanning($group_id, PlanningParameters $planning_parameters)
    {
        $inserted_planning_id = $this->dao->createPlanning($group_id, $planning_parameters);

        if (isset($planning_parameters->priority_change_permission) && ! empty($planning_parameters->priority_change_permission)) {
            $this->planning_permissions_manager->savePlanningPermissionForUgroups($inserted_planning_id, $group_id, PlanningPermissionsManager::PERM_PRIORITY_CHANGE, $planning_parameters->priority_change_permission);
        }
    }

    /**
     * Update an existing planning
     *
     * @param int $planning_id
     */
    public function updatePlanning($planning_id, $group_id, PlanningParameters $planning_parameters)
    {
        $this->dao->updatePlanning($planning_id, $planning_parameters);

        $this->planning_permissions_manager->savePlanningPermissionForUgroups($planning_id, $group_id, PlanningPermissionsManager::PERM_PRIORITY_CHANGE, $planning_parameters->priority_change_permission);
    }

    /**
     * Delete planning
     *
     * @param $planning_id the id of the planning
     *
     * @return bool
     */
    public function deletePlanning($planning_id)
    {
        return $this->dao->deletePlanning($planning_id);
    }

    /**
     * @param int $group_id the project id the trackers to retrieve belong to
     *
     * @return Array of Tracker
     */
    public function getAvailableBacklogTrackers(PFUser $user, $group_id)
    {
        $potential_planning_trackers = $this->getPotentialPlanningTrackerIds($user, $group_id);
        $backlog_trackers = array();
        foreach ($this->dao->searchNonPlanningTrackersByGroupId($group_id) as $row) {
            if (! in_array($row['id'], $potential_planning_trackers)) {
                $backlog_trackers[] = $this->tracker_factory->getInstanceFromRow($row);
            }
        }
        return $backlog_trackers;
    }

    /**
     * Retrieve the project trackers that can be used as planning trackers.
     *
     * @return Array of Tracker
     */
    public function getAvailablePlanningTrackers(PFUser $user, $group_id)
    {
        $potential_planning_trackers = $this->getPotentialPlanningTrackerIds($user, $group_id);
        if (count($potential_planning_trackers) > 0) {
            $existing_plannings = $this->getPlanningTrackerIdsByGroupId($group_id);
            $trackers = array();
            foreach ($potential_planning_trackers as $tracker_id) {
                if (! in_array($tracker_id, $existing_plannings)) {
                    $trackers[] = $this->tracker_factory->getTrackerById($tracker_id);
                }
            }
            return $trackers;
        } else {
            return array_values($this->tracker_factory->getTrackersByGroupId($group_id));
        }
    }

    /**
     * Return trackers that could be used as planning tracker
     *
     * We know a tracker can be used as a planning tracker if there is already
     * a planning defined so we get the whole planning tracker family (children
     * and parents)
     *
     * @param int $group_id
     *
     * @return Tracker[]
     */
    public function getPotentialPlanningTrackers(PFUser $user, $group_id)
    {
        $trackers = array();
        foreach ($this->getPotentialPlanningTrackerIds($user, $group_id) as $tracker_id) {
            $tracker = $this->tracker_factory->getTrackerById($tracker_id);
            if ($tracker !== null) {
                $trackers[] = $tracker;
            }
        }
        return $trackers;
    }

    /**
     * Return ids of tracker that could be used as planning tracker
     *
     * We know a tracker can be used as a planning tracker if there is already
     * a planning defined so we get the whole planning tracker family (children
     * and parents)
     *
     * @param int $group_id
     *
     * @return int[]
     */
    protected function getPotentialPlanningTrackerIds(PFUser $user, $group_id)
    {
        $root_planning = $this->getRootPlanning($user, $group_id);
        if ($root_planning) {
            return $this->tracker_factory->getHierarchyFactory()->getHierarchy(
                array($root_planning->getPlanningTracker()->getId())
            )->flatten();
        } else {
            return array();
        }
    }

    public function getPlanningsOutOfRootPlanningHierarchy(PFUser $user, $group_id)
    {
        $plannings = array();
        $potential_planning_trackers = $this->getPotentialPlanningTrackerIds($user, $group_id);
        if ($potential_planning_trackers) {
            $existing_planning_tracker_ids = $this->getPlanningTrackerIdsByGroupId($group_id);
            foreach ($existing_planning_tracker_ids as $tracker_id) {
                if (! in_array($tracker_id, $potential_planning_trackers)) {
                    $tracker = $this->tracker_factory->getTrackerById($tracker_id);
                    if ($tracker !== null) {
                        $plannings[] = $this->getPlanningByPlanningTracker($tracker);
                    }
                }
            }
        }
        return $plannings;
    }

    /**
     * @return TrackerFactory
     */
    public function getTrackerFactory()
    {
        return $this->tracker_factory;
    }


    /**
     * Return the 'Planning' tracker (tracker we should be able to use artifacts to perform search.
     *
     * @param int $group_id
     *
     * @return Array of Integer
     */
    public function getPlanningTrackers($group_id, PFUser $user)
    {
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

    public function getChildrenPlanning(Planning $planning)
    {
        $children = $this->tracker_factory->getHierarchyFactory()->getChildren($planning->getPlanningTrackerId());
        if (count($children) == 0) {
            return null;
        } else {
            $planning_tracker = array_shift($children);
            return $this->getPlanningByPlanningTracker($planning_tracker);
        }
    }

    /**
     * @return Planning[]
     */
    public function getSubPlannings(Planning $base_planning, PFUser $user)
    {
        $all_plannings = $this->getOrderedPlanningsWithBacklogTracker($user, $base_planning->getGroupId());
        $sub_plannings = [];
        foreach ($all_plannings as $key => $planning) {
            if ($planning->getId() == $base_planning->getId()) {
                $sub_plannings = array_slice($all_plannings, $key + 1);
                break;
            }
        }

        return $sub_plannings;
    }
}
