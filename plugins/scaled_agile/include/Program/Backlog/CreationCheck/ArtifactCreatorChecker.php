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

namespace Tuleap\ScaledAgile\Program\Backlog\CreationCheck;

use PFUser;
use Tuleap\ScaledAgile\Program\BuildPlanning;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\ScaledAgile\Project;
use Tuleap\ScaledAgile\ScaledAgileTracker;

class ArtifactCreatorChecker
{
    /**
     * @var ProgramIncrementArtifactCreatorChecker
     */
    private $program_increment_artifact_creator_checker;
    /**
     * @var BuildPlanning
     */
    private $build_planning;

    public function __construct(
        BuildPlanning $build_planning,
        ProgramIncrementArtifactCreatorChecker $program_increment_artifact_creator_checker
    ) {
        $this->program_increment_artifact_creator_checker = $program_increment_artifact_creator_checker;
        $this->build_planning                             = $build_planning;
    }

    public function canCreateAnArtifact(PFUser $user, ScaledAgileTracker $tracker_data, Project $project_data): bool
    {
        try {
            $root_planning = $this->build_planning->buildRootPlanning(
                $user,
                $project_data->getId()
            );
        } catch (TopPlanningNotFoundInProjectException $e) {
            return true;
        }

        if ($root_planning->getPlanningTracker()->getTrackerId() !== $tracker_data->getTrackerId()) {
            return true;
        }

        return $this->program_increment_artifact_creator_checker->canProgramIncrementBeCreated($root_planning, $user);
    }
}
