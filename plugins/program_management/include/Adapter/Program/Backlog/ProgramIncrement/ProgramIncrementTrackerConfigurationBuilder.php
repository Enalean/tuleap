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

use Tuleap\ProgramManagement\Adapter\Program\Tracker\ProgramTrackerException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\BuildPlanProgramIncrementConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\BuildProgramIncrementTrackerConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementTrackerConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanStore;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;

class ProgramIncrementTrackerConfigurationBuilder implements BuildProgramIncrementTrackerConfiguration
{
    /**
     * @var BuildPlanProgramIncrementConfiguration
     */
    private $plan_configuration_builder;
    /**
     * @var PlanStore
     */
    private $plan_store;

    public function __construct(
        BuildPlanProgramIncrementConfiguration $plan_configuration_builder,
        PlanStore $plan_store
    ) {
        $this->plan_configuration_builder = $plan_configuration_builder;
        $this->plan_store                 = $plan_store;
    }

    /**
     * @throws \Tuleap\ProgramManagement\Adapter\Program\Plan\PlanTrackerException
     * @throws ProgramTrackerException
     * @throws \Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\PlanCheckException
     */
    public function build(\PFUser $user, ProgramIdentifier $project): ProgramIncrementTrackerConfiguration
    {
        $tracker                      = $this->plan_configuration_builder->buildTrackerProgramIncrementFromProjectId(
            $project->getId(),
            $user
        );
        $can_create_program_increment = $tracker->userCanSubmitArtifact($user);

        $program_increments_labels   = $this->plan_store->getProgramIncrementLabels($tracker->getTrackerId());
        $program_increment_label     = null;
        $program_increment_sub_label = null;

        if ($program_increments_labels !== null) {
            $program_increment_label     = $program_increments_labels['label'];
            $program_increment_sub_label = $program_increments_labels['sub_label'];
        }

        return new ProgramIncrementTrackerConfiguration(
            $tracker->getTrackerId(),
            $can_create_program_increment,
            $program_increment_label,
            $program_increment_sub_label
        );
    }
}
