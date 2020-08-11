<?php
/*
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

use Tuleap\MultiProjectBacklog\Aggregator\ContributorProjectsCollection;

class MilestoneTrackerCollectionBuilder
{
    /**
     * @var \PlanningFactory
     */
    private $planning_factory;

    public function __construct(\PlanningFactory $planning_factory)
    {
        $this->planning_factory = $planning_factory;
    }

    /**
     * @throws MilestoneTrackerRetrievalException
     */
    public function buildFromAggregatorProjectAndItsContributors(
        \Project $aggregator_project,
        ContributorProjectsCollection $contributor_projects_collection,
        \PFUser $user
    ): MilestoneTrackerCollection {
        $projects = array_values(
            array_merge([$aggregator_project], $contributor_projects_collection->getContributorProjects())
        );
        $trackers = [];
        foreach ($projects as $project) {
            $root_planning = $this->planning_factory->getRootPlanning($user, (int) $project->getID());
            if (! $root_planning) {
                throw new MissingRootPlanningException((int) $project->getID());
            }
            $milestone_tracker = $root_planning->getPlanningTracker();
            if (! $milestone_tracker || $milestone_tracker instanceof \NullTracker) {
                throw new NoMilestoneTrackerException($root_planning->getId());
            }
            $trackers[] = $milestone_tracker;
        }
        return new MilestoneTrackerCollection($trackers);
    }
}
