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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\BuildPlanProgramIncrementConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\BuildProgramIncrementTrackerConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementTrackerConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\RetrieveProgramIncrementLabels;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;

class ProgramIncrementTrackerConfigurationBuilder implements BuildProgramIncrementTrackerConfiguration
{
    private BuildPlanProgramIncrementConfiguration $plan_configuration_builder;
    private RetrieveProgramIncrementLabels $label_retriever;

    public function __construct(
        BuildPlanProgramIncrementConfiguration $plan_configuration_builder,
        RetrieveProgramIncrementLabels $label_retriever
    ) {
        $this->plan_configuration_builder = $plan_configuration_builder;
        $this->label_retriever            = $label_retriever;
    }

    public function build(ProgramIdentifier $project, \PFUser $user): ProgramIncrementTrackerConfiguration
    {
        $tracker = $this->plan_configuration_builder->buildProgramIncrementTrackerFromProgram($project, $user);

        $can_create_program_increment = $tracker->userCanSubmitArtifact($user);

        $program_increments_labels = ProgramIncrementLabels::fromProgramIncrementTracker(
            $this->label_retriever,
            $tracker
        );
        return new ProgramIncrementTrackerConfiguration(
            $tracker->getTrackerId(),
            $can_create_program_increment,
            $program_increments_labels
        );
    }
}
