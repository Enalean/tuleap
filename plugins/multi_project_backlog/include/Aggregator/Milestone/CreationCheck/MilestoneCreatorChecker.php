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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\CreationCheck;

use PFUser;
use Planning_VirtualTopMilestone;
use Tuleap\MultiProjectBacklog\Aggregator\ContributorProjectsCollectionBuilder;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerCollectionBuilder;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerRetrievalException;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFieldCollectionBuilder;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFieldRetrievalException;

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
     * @var SynchronizedFieldCollectionBuilder
     */
    private $field_collection_builder;
    /**
     * @var SemanticChecker
     */
    private $semantic_checker;

    public function __construct(
        ContributorProjectsCollectionBuilder $contributor_projects_collection_builder,
        MilestoneTrackerCollectionBuilder $milestone_trackers_builder,
        SynchronizedFieldCollectionBuilder $field_collection_builder,
        SemanticChecker $semantic_checker
    ) {
        $this->projects_builder         = $contributor_projects_collection_builder;
        $this->trackers_builder         = $milestone_trackers_builder;
        $this->field_collection_builder = $field_collection_builder;
        $this->semantic_checker         = $semantic_checker;
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
        if (! $this->semantic_checker->areTrackerSemanticsWellConfigured($top_milestone, $milestone_tracker_collection)) {
            return false;
        }
        if (! $milestone_tracker_collection->canUserSubmitAnArtifactInAllTrackers($user)) {
            return false;
        }

        try {
            $fields = $this->field_collection_builder->buildFromMilestoneTrackers($milestone_tracker_collection, $user);
        } catch (SynchronizedFieldRetrievalException $exception) {
            return false;
        }
        if (! $fields->canUserSubmitAndUpdateAllFields($user)) {
            return false;
        }

        return true;
    }
}
