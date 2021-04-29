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
use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanTrackerException;
use Tuleap\ProgramManagement\Adapter\Program\Tracker\ProgramTrackerException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\BuildPlanProgramIncrementConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\PlanCheckException;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Project;

class ArtifactCreatorChecker
{
    /**
     * @var ProgramIncrementArtifactCreatorChecker
     */
    private $program_increment_artifact_creator_checker;
    /**
     * @var BuildPlanProgramIncrementConfiguration
     */
    private $build_plan_configuration;

    public function __construct(
        ProgramIncrementArtifactCreatorChecker $program_increment_artifact_creator_checker,
        BuildPlanProgramIncrementConfiguration $build_plan_configuration
    ) {
        $this->program_increment_artifact_creator_checker = $program_increment_artifact_creator_checker;
        $this->build_plan_configuration                   = $build_plan_configuration;
    }

    public function canCreateAnArtifact(PFUser $user, ProgramTracker $tracker_data, Project $project_data): bool
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

        return $this->program_increment_artifact_creator_checker->canProgramIncrementBeCreated($tracker_data, $project_data, $user);
    }
}
