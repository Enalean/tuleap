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

namespace Tuleap\ScaledAgile\Adapter\Program\Backlog\ProgramIncrement;

use Tuleap\ScaledAgile\Adapter\Program\Tracker\ProgramTrackerException;
use Tuleap\ScaledAgile\Program\Backlog\Plan\BuildPlanProgramIncrementConfiguration;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\BuildProgramIncrementTrackerConfiguration;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\ProgramIncrementTrackerConfiguration;
use Tuleap\ScaledAgile\Program\Program;

class ProgramIncrementTrackerConfigurationBuilder implements BuildProgramIncrementTrackerConfiguration
{
    /**
     * @var BuildPlanProgramIncrementConfiguration
     */
    private $plan_configuration_builder;

    public function __construct(
        BuildPlanProgramIncrementConfiguration $plan_configuration_builder
    ) {
        $this->plan_configuration_builder = $plan_configuration_builder;
    }

    /**
     * @throws \Tuleap\ScaledAgile\Adapter\Program\Plan\PlanTrackerException
     * @throws ProgramTrackerException
     * @throws \Tuleap\ScaledAgile\Program\Backlog\Plan\PlanCheckException
     */
    public function build(\PFUser $user, Program $project): ProgramIncrementTrackerConfiguration
    {
        $tracker                      = $this->plan_configuration_builder->buildTrackerProgramIncrementFromProjectId(
            $project->getId(),
            $user
        );
        $can_create_program_increment = $tracker->userCanSubmitArtifact($user);

        return new ProgramIncrementTrackerConfiguration($tracker->getTrackerId(), $can_create_program_increment);
    }
}
