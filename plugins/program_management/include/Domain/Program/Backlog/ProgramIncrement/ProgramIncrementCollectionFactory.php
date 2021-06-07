<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\BuildPlanProgramIncrementConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollectionFactory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\BuildPlanning;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\Planning;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\PlanningNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Project;

class ProgramIncrementCollectionFactory implements TrackerCollectionFactory
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

    public function buildFromProgramProjectAndItsTeam(
        ProgramIdentifier $program,
        TeamProjectsCollection $team_projects_collection,
        \PFUser $user
    ): SourceTrackerCollection {
        $trackers = [];

        $trackers[] = $this->configuration_builder->buildProgramIncrementTrackerFromProgram($program, $user);

        foreach ($team_projects_collection->getTeamProjects() as $project) {
            $trackers[] = $this->getPlannableTracker($user, $project);
        }

        return new SourceTrackerCollection($trackers);
    }

    /**
     * @throws PlanningNotFoundException
     * @throws TrackerRetrievalException
     */
    private function getPlannableTracker(\PFUser $user, Project $project): ProgramTracker
    {
        $root_planning = Planning::buildPlanning($this->planning_adapter, $user, $project->getID());

        return $root_planning->getPlanningTracker();
    }
}
