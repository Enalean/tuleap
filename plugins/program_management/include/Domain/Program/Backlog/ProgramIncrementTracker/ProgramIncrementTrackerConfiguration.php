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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker;

use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramHasNoProgramIncrementTrackerException;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyPrioritizeFeaturesPermission;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyUserCanSubmit;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * @psalm-immutable
 */
final class ProgramIncrementTrackerConfiguration
{
    private function __construct(
        private int $program_increment_tracker_id,
        private bool $can_create_program_increment,
        private bool $has_plan_permissions,
        private ProgramIncrementLabels $program_increment_labels,
    ) {
    }

    /**
     * @throws ProgramTrackerNotFoundException
     * @throws ProgramHasNoProgramIncrementTrackerException
     */
    public static function fromProgram(
        RetrieveVisibleProgramIncrementTracker $retrieve_tracker,
        RetrieveProgramIncrementLabels $retrieve_labels,
        VerifyPrioritizeFeaturesPermission $prioritize_features_permission,
        VerifyUserCanSubmit $user_can_submit_in_tracker_verifier,
        ProgramIdentifier $program,
        UserIdentifier $user_identifier,
    ): self {
        $program_increment_tracker = $retrieve_tracker->retrieveVisibleProgramIncrementTracker(
            $program,
            $user_identifier
        );

        $can_create_program_increment = $user_can_submit_in_tracker_verifier->canUserSubmitArtifact($user_identifier, $program_increment_tracker);
        $has_plan_permissions         = $prioritize_features_permission->canUserPrioritizeFeatures($program, $user_identifier, null);

        $program_increments_labels = ProgramIncrementLabels::fromProgramIncrementTracker(
            $retrieve_labels,
            $program_increment_tracker
        );

        return new self(
            $program_increment_tracker->getId(),
            $can_create_program_increment,
            $has_plan_permissions,
            $program_increments_labels
        );
    }

    public function canCreateProgramIncrement(): bool
    {
        return $this->can_create_program_increment;
    }

    public function getProgramIncrementTrackerId(): int
    {
        return $this->program_increment_tracker_id;
    }

    public function getProgramIncrementLabel(): ?string
    {
        return $this->program_increment_labels->label;
    }

    public function getProgramIncrementSubLabel(): ?string
    {
        return $this->program_increment_labels->sub_label;
    }

    public function hasPlanPermissions(): bool
    {
        return $this->has_plan_permissions;
    }
}
