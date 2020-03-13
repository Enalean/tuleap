<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;

/**
 * Returns all tasks id in a Release
 *
 * It leverage on ArtifactLink information and will recrusively inspect the
 * milestone links (from top to bottom) and keep all artifacts that belongs to
 * the backlog tracker.
 *
 * This is the same type of algorithm than used in AgileDashboard_Milestone_Backlog_ArtifactsFinder
 */
class AgileDashboard_BacklogItem_SubBacklogItemProvider
{

    /** @var Tracker_ArtifactDao */
    private $dao;

    /** @var array */
    private $backlog_ids = array();

    /** @var int[] */
    private $inspected_ids = array();

    /** @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory */
    private $backlog_item_collection_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogFactory */
    private $backlog_factory;

    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;

    public function __construct(
        Tracker_ArtifactDao $dao,
        AgileDashboard_Milestone_Backlog_BacklogFactory $backlog_factory,
        AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory $backlog_item_collection_factory,
        PlanningFactory $planning_factory,
        ExplicitBacklogDao $explicit_backlog_dao,
        ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao
    ) {
        $this->backlog_item_collection_factory   = $backlog_item_collection_factory;
        $this->backlog_factory                   = $backlog_factory;
        $this->dao                               = $dao;
        $this->planning_factory                  = $planning_factory;
        $this->explicit_backlog_dao              = $explicit_backlog_dao;
        $this->artifacts_in_explicit_backlog_dao = $artifacts_in_explicit_backlog_dao;
    }

    /**
     * Return all indexed ids of artifacts linked on milestone that belong to backlog tracker
     *
     * @return array
     */
    public function getMatchingIds(Planning_Milestone $milestone, Tracker $backlog_tracker, PFUser $user)
    {
        if (! $milestone->getArtifactId()) {
            return $this->getMatchingIdsForTopBacklog($milestone, $backlog_tracker, $user);
        }

        return $this->getMatchingIdsForMilestone($milestone, $backlog_tracker, $user);
    }

    private function getMatchingIdsForMilestone(Planning_Milestone $milestone, Tracker $backlog_tracker, PFUser $user)
    {
        $milestone_id_seed = array($milestone->getArtifactId());
        $this->inspected_ids = $milestone_id_seed;

        $filtrable_backlog_tracker_ids = $this->getSubPlanningTrackerIds($milestone, $user);
        $this->filterBacklogIds($backlog_tracker->getId(), $milestone_id_seed, $filtrable_backlog_tracker_ids);

        return $this->backlog_ids;
    }

    private function getMatchingIdsForTopBacklog(Planning_VirtualTopMilestone $milestone, Tracker $backlog_tracker, PFUser $user)
    {
        $project_id = (int) $milestone->getProject()->getID();
        if ($this->explicit_backlog_dao->isProjectUsingExplicitBacklog($project_id)) {
            foreach ($this->artifacts_in_explicit_backlog_dao->getAllTopBacklogItemsForProjectSortedByRank($project_id) as $row) {
                $this->backlog_ids[$row['artifact_id']] = true;
            }
        } else {
            $backlog_unassigned = $this->backlog_factory->getSelfBacklog($milestone);
            $backlog_items      = $this->backlog_item_collection_factory->getUnassignedOpenCollection($user, $milestone, $backlog_unassigned, false);

            foreach ($backlog_items as $backlog_item) {
                if ($backlog_item->getArtifact()->getTrackerId() == $backlog_tracker->getId()) {
                    $this->backlog_ids[$backlog_item->getArtifact()->getId()] = true;
                }
            }
        }

        return $this->backlog_ids;
    }

    /**
     * Retrieve all linked artifacts and keep only those that belong to backlog tracker
     *
     * We need to keep list of ids we already looked at so we avoid cycles.
     */
    private function filterBacklogIds($backlog_tracker_id, array $artifacts, array $filtrable_planning_tracker_ids)
    {
        $artifacts_to_inspect = array();
        foreach ($this->dao->getLinkedArtifactsByIds($artifacts, $this->inspected_ids) as $artifact_row) {
            $artifact_row_tracker_id = $artifact_row['tracker_id'];

            if (! $this->planning_factory->isTrackerIdUsedInAPlanning($artifact_row['tracker_id']) ||
                in_array($artifact_row_tracker_id, $filtrable_planning_tracker_ids)
            ) {
                $artifacts_to_inspect[] = $artifact_row['id'];
            }

            if ($artifact_row['tracker_id'] == $backlog_tracker_id) {
                $this->backlog_ids[$artifact_row['id']] = true;
            }

            $this->inspected_ids[] = $artifact_row['id'];
        }

        if (count($artifacts_to_inspect) > 0) {
            $this->filterBacklogIds($backlog_tracker_id, $artifacts_to_inspect, $filtrable_planning_tracker_ids);
        }
    }

    /**
     * @return int[]
     */
    private function getSubPlanningTrackerIds(Planning_Milestone $milestone, PFUser $user)
    {
        $planning_tracker_ids = [];
        foreach ($this->planning_factory->getSubPlannings($milestone->getPlanning(), $user) as $sub_planning) {
            $planning_tracker_ids[] = $sub_planning->getPlanningTrackerId();
        }

        return $planning_tracker_ids;
    }
}
