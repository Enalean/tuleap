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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Plan;

use Project;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanTrackerException;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerException;
use Tuleap\ProgramManagement\Domain\ProgramTracker;

final class ConfigurationChecker
{
    /**
     * @var BuildPlanProgramConfiguration
     */
    private $plan_program_builder;
    /**
     * @var BuildPlanProgramIncrementConfiguration
     */
    private $plan_program_increment_builder;

    public function __construct(
        BuildPlanProgramConfiguration $plan_program_builder,
        BuildPlanProgramIncrementConfiguration $plan_program_increment_builder
    ) {
        $this->plan_program_builder           = $plan_program_builder;
        $this->plan_program_increment_builder = $plan_program_increment_builder;
    }

    /**
     * @throws PlanCheckException
     * @throws PlanTrackerException
     * @throws ProgramTrackerException
     */
    public function getProgramIncrementTracker(\PFUser $user, Project $project): ?ProgramTracker
    {
        $program = $this->plan_program_builder->buildProgramIdentifierFromTeamProject($project, $user);

        if (! $program) {
            return null;
        }

        return $this->plan_program_increment_builder->buildTrackerProgramIncrementFromProjectId($program->getId(), $user);
    }
}
