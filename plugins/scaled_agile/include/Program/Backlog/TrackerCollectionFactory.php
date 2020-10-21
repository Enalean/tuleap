<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Backlog;

use Tuleap\ScaledAgile\Program\Backlog\CreationCheck\MissingRootPlanningException;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\NoProjectIncrementException;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Project\TeamProjectsCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Tracker\ProjectIncrementsTrackerCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Tracker\ProjectIncrementTrackerRetrievalException;
use Tuleap\ScaledAgile\Program\Backlog\Source\SourceTrackerCollection;

class TrackerCollectionFactory
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
     * @throws ProjectIncrementTrackerRetrievalException
     */
    public function buildFromProgramProjectAndItsTeam(
        \Project $program_project,
        TeamProjectsCollection $team_projects_collection,
        \PFUser $user
    ): SourceTrackerCollection {
        $projects = array_values(
            array_merge([$program_project], $team_projects_collection->getTeamProjects())
        );
        $trackers = [];
        foreach ($projects as $project) {
            $trackers[] = $this->getProjectIncrementTracker($user, $project);
        }
        return new SourceTrackerCollection($trackers);
    }

    /**
     * @throws ProjectIncrementTrackerRetrievalException
     */
    public function buildFromTeamProjects(
        TeamProjectsCollection $team_projects_collection,
        \PFUser $user
    ): ProjectIncrementsTrackerCollection {
        $trackers = [];
        foreach ($team_projects_collection->getTeamProjects() as $team_projects) {
            $trackers[] = $this->getProjectIncrementTracker($user, $team_projects);
        }
        return new ProjectIncrementsTrackerCollection($trackers);
    }

    /**
     * @throws ProjectIncrementTrackerRetrievalException
     */
    private function getProjectIncrementTracker(\PFUser $user, \Project $project): \Tracker
    {
        $root_planning = $this->planning_factory->getRootPlanning($user, (int) $project->getID());
        if (! $root_planning) {
            throw new MissingRootPlanningException((int) $project->getID());
        }
        $project_increment = $root_planning->getPlanningTracker();
        if (! $project_increment || $project_increment instanceof \NullTracker) {
            throw new NoProjectIncrementException($root_planning->getId());
        }
        return $project_increment;
    }
}
