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

    public function __construct(
        ContributorProjectsCollectionBuilder $contributor_projects_collection_builder,
        MilestoneTrackerCollectionBuilder $milestone_trackers_builder,
        \Tracker_Semantic_TitleDao $semantic_title_dao
    ) {
        $this->projects_builder   = $contributor_projects_collection_builder;
        $this->trackers_builder   = $milestone_trackers_builder;
        $this->semantic_title_dao = $semantic_title_dao;
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
            $milestone_tracker_collection = $this->trackers_builder->buildFromContributorProjects(
                $contributor_projects_collection,
                $user
            );
        } catch (MilestoneTrackerRetrievalException $exception) {
            return false;
        }

        $nb_of_trackers_without_title = $this->semantic_title_dao->getNbOfTrackerWithoutSemanticTitleDefined(
            $milestone_tracker_collection->getTrackerIds()
        );
        if ($nb_of_trackers_without_title > 0) {
            return false;
        }

        return true;
    }
}
