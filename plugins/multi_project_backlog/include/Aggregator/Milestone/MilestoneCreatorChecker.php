<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone;

use PFUser;
use Planning_VirtualTopMilestone;
use Tuleap\MultiProjectBacklog\Aggregator\ContributorProjectsCollectionBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;

class MilestoneCreatorChecker
{
    /**
     * @var ContributorProjectsCollectionBuilder
     */
    private $projects_builder;
    /**
     * @var MilestoneTrackerCollectionBuilder
     */
    private $trackers_builder;
    /**
     * @var \Tracker_Semantic_TitleDao
     */
    private $semantic_title_dao;
    /**
     * @var \Tracker_Semantic_DescriptionDao
     */
    private $semantic_description_dao;

    /**
     * @var SemanticTimeframeDao
     */
    private $semantic_timeframe_dao;

    /**
     * @var MilestoneCreatorSemanticStatusChecker
     */
    private $semantic_status_checker;

    public function __construct(
        ContributorProjectsCollectionBuilder $contributor_projects_collection_builder,
        MilestoneTrackerCollectionBuilder $milestone_trackers_builder,
        \Tracker_Semantic_TitleDao $semantic_title_dao,
        \Tracker_Semantic_DescriptionDao $semantic_description_dao,
        SemanticTimeframeDao $semantic_timeframe_dao,
        MilestoneCreatorSemanticStatusChecker $semantic_status_checker
    ) {
        $this->projects_builder         = $contributor_projects_collection_builder;
        $this->trackers_builder         = $milestone_trackers_builder;
        $this->semantic_title_dao       = $semantic_title_dao;
        $this->semantic_description_dao = $semantic_description_dao;
        $this->semantic_timeframe_dao   = $semantic_timeframe_dao;
        $this->semantic_status_checker  = $semantic_status_checker;
    }

    public function canMilestoneBeCreated(Planning_VirtualTopMilestone $top_milestone, PFUser $user): bool
    {
        $aggregator_project = $top_milestone->getProject();

        $contributor_projects_collection = $this->projects_builder->getContributorProjectForAGivenAggregatorProject(
            $aggregator_project
        );
        if ($contributor_projects_collection->isEmpty()) {
            return true;
        }
        try {
            $milestone_tracker_collection = $this->trackers_builder->buildFromAggregatorProjectAndItsContributors(
                $aggregator_project,
                $contributor_projects_collection,
                $user
            );
        } catch (MilestoneTrackerRetrievalException $exception) {
            return false;
        }

        $tracker_ids = $milestone_tracker_collection->getTrackerIds();
        if ($this->semantic_title_dao->getNbOfTrackerWithoutSemanticTitleDefined($tracker_ids) > 0) {
            return false;
        }
        if ($this->semantic_description_dao->getNbOfTrackerWithoutSemanticDescriptionDefined($tracker_ids) > 0) {
            return false;
        }
        if (! $this->areTimeFrameSemanticsAligned($tracker_ids)) {
            return false;
        }

        if ($this->semantic_status_checker->areSemanticStatusWellConfigured($top_milestone, $milestone_tracker_collection) === false) {
            return false;
        }

        return true;
    }

    /**
     * @param int[] $tracker_ids
     */
    private function areTimeFrameSemanticsAligned(array $tracker_ids): bool
    {
        if ($this->semantic_timeframe_dao->getNbOfTrackersWithoutTimeFrameSemanticDefined($tracker_ids) > 0) {
            return false;
        }
        if (! $this->semantic_timeframe_dao->areTimeFrameSemanticsUsingSameTypeOfField($tracker_ids)) {
            return false;
        }
        return true;
    }
}
