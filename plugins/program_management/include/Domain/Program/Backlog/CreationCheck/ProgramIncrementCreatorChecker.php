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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck;

use PFUser;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\BuildPlanProgramIncrementConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\PlanCheckException;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanTrackerException;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerException;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Project;

class ProgramIncrementCreatorChecker
{
    /**
     * @var TimeboxCreatorChecker
     */
    private $timebox_creator_checker;
    /**
     * @var BuildPlanProgramIncrementConfiguration
     */
    private $build_plan_configuration;

    public function __construct(
        TimeboxCreatorChecker $timebox_creator_checker,
        BuildPlanProgramIncrementConfiguration $build_plan_configuration
    ) {
        $this->timebox_creator_checker  = $timebox_creator_checker;
        $this->build_plan_configuration = $build_plan_configuration;
    }

    public function canCreateAProgramIncrement(PFUser $user, ProgramTracker $tracker_data, Project $project_data): bool
    {
        try {
            $program_increment_tracker = $this->build_plan_configuration->buildTrackerProgramIncrementFromProjectId(
                $project_data->getId(),
                $user
            );
        } catch (PlanCheckException | PlanTrackerException | ProgramTrackerException $e) {
            return true;
        }
        if ($program_increment_tracker->getTrackerId() !== $tracker_data->getTrackerId()) {
            return true;
        }

        return $this->timebox_creator_checker->canTimeboxBeCreated($tracker_data, $project_data, $user);
    }
}
