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

namespace Tuleap\ScaledAgile\Program\Milestone;

use Tuleap\ScaledAgile\Program\TeamProjectsCollection;

class MilestoneTrackerCollectionFactory
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
    public function buildFromProgramProjectAndItsTeam(
        \Project $program_project,
        TeamProjectsCollection $team_projects_collection,
        \PFUser $user
    ): MilestoneTrackerCollection {
        $projects = array_values(
            array_merge([$program_project], $team_projects_collection->getTeamProjects())
        );
        $trackers = [];
        foreach ($projects as $project) {
            $trackers[] = $this->getTopMilestoneTracker($user, $project);
        }
        return new MilestoneTrackerCollection($trackers);
    }

    /**
     * @throws MilestoneTrackerRetrievalException
     */
    public function buildFromTeamProjects(
        TeamProjectsCollection $team_projects_collection,
        \PFUser $user
    ): TeamMilestoneTrackerCollection {
        $trackers = [];
        foreach ($team_projects_collection->getTeamProjects() as $team_projects) {
            $trackers[] = $this->getTopMilestoneTracker($user, $team_projects);
        }
        return new TeamMilestoneTrackerCollection($trackers);
    }

    /**
     * @throws MilestoneTrackerRetrievalException
     */
    private function getTopMilestoneTracker(\PFUser $user, \Project $project): \Tracker
    {
        $root_planning = $this->planning_factory->getRootPlanning($user, (int) $project->getID());
        if (! $root_planning) {
            throw new MissingRootPlanningException((int) $project->getID());
        }
        $milestone_tracker = $root_planning->getPlanningTracker();
        if (! $milestone_tracker || $milestone_tracker instanceof \NullTracker) {
            throw new NoMilestoneTrackerException($root_planning->getId());
        }
        return $milestone_tracker;
    }
}
