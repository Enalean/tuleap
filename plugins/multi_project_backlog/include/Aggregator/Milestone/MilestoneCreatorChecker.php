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
use PlanningFactory;
use Tuleap\MultiProjectBacklog\Aggregator\ContributorProjectsCollectionBuilder;

class MilestoneCreatorChecker
{
    /**
     * @var ContributorProjectsCollectionBuilder
     */
    private $contributor_projects_collection_builder;

    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    public function __construct(
        ContributorProjectsCollectionBuilder $contributor_projects_collection_builder,
        PlanningFactory $planning_factory
    ) {
        $this->contributor_projects_collection_builder = $contributor_projects_collection_builder;
        $this->planning_factory                        = $planning_factory;
    }

    public function canMilestoneBeCreated(Planning_VirtualTopMilestone $top_milestone, PFUser $user): bool
    {
        $aggregator_project = $top_milestone->getProject();

        $contributor_projects_collection = $this->contributor_projects_collection_builder->getContributorProjectForAGivenAggregatorProject(
            $aggregator_project
        );

        foreach ($contributor_projects_collection->getContributorProjects() as $contributor_project) {
            $root_planning = $this->planning_factory->getRootPlanning(
                $user,
                (int) $contributor_project->getID()
            );

            if (! $root_planning) {
                return false;
            }
        }

        return true;
    }
}
