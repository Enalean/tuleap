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

use Tuleap\ScaledAgile\Adapter\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ScaledAgile\Adapter\Program\PlanningCheck\ConfigurationUserCanNotSeeProgramException;
use Tuleap\ScaledAgile\Adapter\Program\PlanningCheck\ProgramNotFoundException;
use Tuleap\ScaledAgile\Adapter\Program\PlanningCheck\UserCanNotAccessToProgramException;
use Tuleap\ScaledAgile\Adapter\Program\Tracker\ProgramTrackerNotFoundException;
use Tuleap\ScaledAgile\Program\Plan\ProgramIncrementTracker;
use Tuleap\ScaledAgile\Program\Program;

interface BuildPlanningConfiguration
{
    /**
     * @throws ConfigurationUserCanNotSeeProgramException
     * @throws ProgramNotFoundException
     * @throws ProgramTrackerNotFoundException
     */
    public function buildProgramIncrementFromProjectId(int $project_id, \PFUser $user): ProgramIncrementTracker;

    /**
     * @throws ProjectIsNotAProgramException
     * @throws UserCanNotAccessToProgramException
     */
    public function buildProgramFromTeamProject(\Project $project, \PFUser $user): Program;
}
