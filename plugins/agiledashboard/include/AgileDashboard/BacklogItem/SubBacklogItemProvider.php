<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\BacklogItem;

use AgileDashboard_Milestone_Backlog_BacklogFactory;
use PFUser;
use Planning_Milestone;
use PlanningFactory;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\Milestone\Backlog\BacklogItemCollectionFactory;
use Tuleap\Tracker\Artifact\Dao\ArtifactDao;
use Tuleap\Tracker\Tracker;

/**
 * Returns all tasks id in a Release
 *
 * It leverages on ArtifactLink information and will recrusively inspect the
 * milestone links (from top to bottom) and keep all artifacts that belongs to
 * the backlog tracker.
 *
 * This is the same type of algorithm than used in AgileDashboard_Milestone_Backlog_ArtifactsFinder
 */
final class SubBacklogItemProvider
{
    /** @var array<int, bool> */
    private array $backlog_ids = [];
    /** @var int[] */
    private array $inspected_ids = [];

    public function __construct(
        private readonly ArtifactDao $dao,
        private readonly AgileDashboard_Milestone_Backlog_BacklogFactory $backlog_factory,
        private readonly BacklogItemCollectionFactory $backlog_item_collection_factory,
        private readonly PlanningFactory $planning_factory,
        private readonly ExplicitBacklogDao $explicit_backlog_dao,
        private readonly ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao,
    ) {
    }

    /**
     * Return all indexed ids of artifacts linked on milestone that belong to backlog tracker
     *
     * @return array<int, bool>
     */
    public function getMatchingIds(Planning_Milestone $milestone, Tracker $backlog_tracker, PFUser $user): array
    {
        if ($milestone->getArtifactId() === null) {
            return $this->getMatchingIdsForTopBacklog($milestone, $backlog_tracker, $user);
        }

        return $this->getMatchingIdsForMilestone($milestone, $backlog_tracker, $user);
    }

    /**
     * @return array<int, bool>
     */
    private function getMatchingIdsForMilestone(Planning_Milestone $milestone, Tracker $backlog_tracker, PFUser $user): array
    {
        $milestone_id_seed             = [];
        $milestone_id_seed['planning'] = [$milestone->getArtifactId() ?? 0];
        $this->inspected_ids           = $milestone_id_seed['planning'];

        $filtrable_backlog_tracker_ids = $this->getSubPlanningTrackerIds($milestone, $user);
        $this->filterBacklogIds($backlog_tracker->getId(), $milestone_id_seed, $filtrable_backlog_tracker_ids);

        return $this->backlog_ids;
    }

    /**
     * @return array<int, bool>
     */
    private function getMatchingIdsForTopBacklog(Planning_Milestone $milestone, Tracker $backlog_tracker, PFUser $user): array
    {
        $project_id = (int) $milestone->getProject()->getID();
        if ($this->explicit_backlog_dao->isProjectUsingExplicitBacklog($project_id)) {
            foreach ($this->artifacts_in_explicit_backlog_dao->getAllTopBacklogItemsForProjectSortedByRank($project_id) as $row) {
                $this->backlog_ids[$row['artifact_id']] = true;
            }
        } else {
            $backlog_unassigned = $this->backlog_factory->getSelfBacklog($milestone);
            $backlog_items      = $this->backlog_item_collection_factory->getUnassignedOpenCollection($user, $milestone, $backlog_unassigned, null);

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
    private function filterBacklogIds(int $backlog_tracker_id, array $artifacts, array $filtrable_planning_tracker_ids): void
    {
        $children               = [];
        $artifacts_for_planning = [];

        if (isset($artifacts['planning'])) {
            $artifacts_for_planning = $this->dao->getLinkedArtifactsByIds($artifacts['planning'], $this->inspected_ids);
        }
        if (isset($artifacts['not_planning'])) {
            $children = $this->getFilteredChildrenFromArtifacts($artifacts['not_planning']);
        }

        $artifacts_in_planning = $this->filterResult(
            $artifacts_for_planning,
            $filtrable_planning_tracker_ids,
            $backlog_tracker_id
        );

        $children_of_artifacts_in_planning = $this->filterResult(
            $children,
            $filtrable_planning_tracker_ids,
            $backlog_tracker_id
        );

        $artifacts_to_inspect = array_merge($artifacts_in_planning, $children_of_artifacts_in_planning);

        if (count($artifacts_to_inspect) > 0) {
            $this->filterBacklogIds($backlog_tracker_id, $artifacts_to_inspect, $filtrable_planning_tracker_ids);
        }
    }

    /**
     * @return int[]
     */
    private function getSubPlanningTrackerIds(Planning_Milestone $milestone, PFUser $user): array
    {
        $planning_tracker_ids = [];
        foreach ($this->planning_factory->getSubPlannings($milestone->getPlanning(), $user) as $sub_planning) {
            $planning_tracker_ids[] = $sub_planning->getPlanningTrackerId();
        }

        return $planning_tracker_ids;
    }

    /**
     * @psalm-param list<array{tracker_id: int, id: int}> $artifacts_for_planning
     * @param int[] $filtrable_planning_tracker_ids
     * @psalm-return array{planning?: list<int>, not_planning?: list<int>}
     */
    private function filterResult(
        array $artifacts_for_planning,
        array $filtrable_planning_tracker_ids,
        int $backlog_tracker_id,
    ): array {
        $artifacts_to_inspect = [];
        foreach ($artifacts_for_planning as $artifact_row) {
            $artifact_row_tracker_id = $artifact_row['tracker_id'];
            $artifact_id             = $artifact_row['id'];

            if (
                ! $this->planning_factory->isTrackerIdUsedInAPlanning($artifact_row_tracker_id)
            ) {
                $artifacts_to_inspect['not_planning'][] = $artifact_id;
            }

            if (in_array($artifact_row_tracker_id, $filtrable_planning_tracker_ids)) {
                $artifacts_to_inspect['planning'][] = $artifact_id;
            }

            if ($artifact_row_tracker_id === $backlog_tracker_id) {
                $this->backlog_ids[$artifact_id] = true;
            }

            $this->inspected_ids[] = $artifact_id;
        }
        return $artifacts_to_inspect;
    }

    /**
     * @param int[] $artifacts
     * @psalm-return list<array{tracker_id: int, id: int}>
     */
    private function getFilteredChildrenFromArtifacts(array $artifacts): array
    {
        $children = [];
        foreach ($this->dao->getChildrenForArtifacts($artifacts) as $child) {
            if (! in_array($child['id'], $this->inspected_ids)) {
                $children[] = $child;
            }
        }

        return $children;
    }
}
