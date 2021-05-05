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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\BuildPlanProgramIncrementConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PlanningHasNoProgramIncrementException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\ProgramIncrementsTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\BuildPlanning;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanTrackerException;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\Planning;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Project;

class TrackerCollectionFactory
{
    /**
     * @var BuildPlanning
     */
    private $planning_adapter;
    /**
     * @var BuildPlanProgramIncrementConfiguration
     */
    private $configuration_builder;

    public function __construct(
        BuildPlanning $planning_adapter,
        BuildPlanProgramIncrementConfiguration $configuration_builder
    ) {
        $this->planning_adapter      = $planning_adapter;
        $this->configuration_builder = $configuration_builder;
    }

    /**
     * @throws Plan\PlanCheckException
     * @throws TopPlanningNotFoundInProjectException
     * @throws PlanTrackerException
     * @throws ProgramTrackerNotFoundException
     * @throws PlanningHasNoProgramIncrementException
     */
    public function buildFromProgramProjectAndItsTeam(
        Project $program_project,
        TeamProjectsCollection $team_projects_collection,
        \PFUser $user
    ): SourceTrackerCollection {
        $trackers = [];

        $trackers[] = $this->configuration_builder->buildTrackerProgramIncrementFromProjectId(
            $program_project->getId(),
            $user
        );

        foreach ($team_projects_collection->getTeamProjects() as $project) {
            $trackers[] = $this->getPlannableTracker($user, $project);
        }

        return new SourceTrackerCollection($trackers);
    }

    /**
     * @throws TopPlanningNotFoundInProjectException
     * @throws PlanningHasNoProgramIncrementException
     */
    public function buildFromTeamProjects(
        TeamProjectsCollection $team_projects_collection,
        \PFUser $user
    ): ProgramIncrementsTrackerCollection {
        $trackers = [];
        foreach ($team_projects_collection->getTeamProjects() as $team_projects) {
            $trackers[] = $this->getPlannableTracker($user, $team_projects);
        }

        return new ProgramIncrementsTrackerCollection($trackers);
    }

    /**
     * @throws TopPlanningNotFoundInProjectException
     * @throws PlanningHasNoProgramIncrementException
     */
    private function getPlannableTracker(\PFUser $user, Project $project): ProgramTracker
    {
        $root_planning = Planning::buildPlanning($this->planning_adapter, $user, $project->getID());

        return $root_planning->getPlanningTracker();
    }
}
