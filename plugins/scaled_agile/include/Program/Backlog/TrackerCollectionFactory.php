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

use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\SourceTrackerCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Team\ProgramIncrementsTrackerCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ScaledAgile\ProjectData;
use Tuleap\ScaledAgile\TrackerData;
use Tuleap\ScaledAgile\Adapter\Program\PlanningAdapter;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;

class TrackerCollectionFactory
{
    /**
     * @var PlanningAdapter
     */
    private $planning_adapter;

    public function __construct(PlanningAdapter $planning_adapter)
    {
        $this->planning_adapter = $planning_adapter;
    }

    /**
     * @throws TopPlanningNotFoundInProjectException
     */
    public function buildFromProgramProjectAndItsTeam(
        ProjectData $program_project,
        TeamProjectsCollection $team_projects_collection,
        \PFUser $user
    ): SourceTrackerCollection {
        $projects = array_values(
            array_merge([$program_project], $team_projects_collection->getTeamProjects())
        );
        $trackers = [];
        foreach ($projects as $project) {
            $trackers[] = $this->getProgramIncrementTracker($user, $project);
        }
        return new SourceTrackerCollection($trackers);
    }

    /**
     * @throws TopPlanningNotFoundInProjectException
     */
    public function buildFromTeamProjects(
        TeamProjectsCollection $team_projects_collection,
        \PFUser $user
    ): ProgramIncrementsTrackerCollection {
        $trackers = [];
        foreach ($team_projects_collection->getTeamProjects() as $team_projects) {
            $trackers[] = $this->getProgramIncrementTracker($user, $team_projects);
        }
        return new ProgramIncrementsTrackerCollection($trackers);
    }

    /**
     * @throws TopPlanningNotFoundInProjectException
     */
    private function getProgramIncrementTracker(\PFUser $user, ProjectData $project): TrackerData
    {
        $root_planning     = $this->planning_adapter->buildRootPlanning($user, (int) $project->getID());
        return $root_planning->getPlanningTrackerData();
    }
}
