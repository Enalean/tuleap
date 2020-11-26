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

namespace Tuleap\ScaledAgile\Program\Backlog\PlanningCheck;

use Project;
use Tuleap\ScaledAgile\Program\Plan\ProgramIncrementTracker;

final class ConfigurationChecker
{
    /**
     * @var BuildPlanningConfiguration
     */
    private $planning_program_adapter;

    public function __construct(BuildPlanningConfiguration $planning_program_adapter)
    {
        $this->planning_program_adapter = $planning_program_adapter;
    }

    /**
     * @throws \Tuleap\ScaledAgile\Adapter\Program\PlanningCheck\ConfigurationUserCanNotSeeProgramException
     * @throws \Tuleap\ScaledAgile\Adapter\Program\PlanningCheck\ProgramNotFoundException
     * @throws \Tuleap\ScaledAgile\Adapter\Program\PlanningCheck\UserCanNotAccessToProgramException
     * @throws \Tuleap\ScaledAgile\Adapter\Program\Plan\ProjectIsNotAProgramException
     */
    public function getProgramIncrementTracker(\PFUser $user, Project $project): ProgramIncrementTracker
    {
        $program = $this->planning_program_adapter->buildProgramFromTeamProject($project, $user);

        return $this->planning_program_adapter->buildProgramIncrementFromProjectId($program->getId(), $user);
    }
}
