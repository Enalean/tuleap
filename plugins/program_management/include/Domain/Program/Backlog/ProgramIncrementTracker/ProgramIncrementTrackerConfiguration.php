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
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * @psalm-immutable
 */
final class ProgramIncrementTrackerConfiguration
{
    private bool $can_create_program_increment;
    private int $program_increment_tracker_id;
    private ProgramIncrementLabels $program_increment_labels;
    private bool $has_plan_permissions;

    private function __construct(
        int $program_increment_tracker_id,
        bool $can_create_program_increment,
        bool $has_plan_permissions,
        ProgramIncrementLabels $program_increment_labels
    ) {
        $this->can_create_program_increment = $can_create_program_increment;
        $this->program_increment_tracker_id = $program_increment_tracker_id;
        $this->program_increment_labels     = $program_increment_labels;
        $this->has_plan_permissions         = $has_plan_permissions;
    }

    /**
     * @throws ProgramTrackerNotFoundException
     * @throws ProgramHasNoProgramIncrementTrackerException
     */
    public static function fromProgram(
        RetrieveVisibleProgramIncrementTracker $retrieve_tracker,
        RetrieveProgramIncrementLabels $retrieve_labels,
        ProgramIdentifier $program,
        VerifyPrioritizeFeaturesPermission $prioritize_features_permission,
        UserIdentifier $user_identifier,
        RetrieveUser $retrieve_user
    ): self {
        $user                      =  $retrieve_user->getUserWithId($user_identifier);
        $program_increment_tracker = ProgramTracker::buildProgramIncrementTrackerFromProgram(
            $retrieve_tracker,
            $program,
            $user
        );

        $can_create_program_increment = $program_increment_tracker->userCanSubmitArtifact($user);
        $has_plan_permissions         = $prioritize_features_permission->canUserPrioritizeFeatures($program, $user_identifier, null);

        $program_increments_labels = ProgramIncrementLabels::fromProgramIncrementTracker(
            $retrieve_labels,
            $program_increment_tracker
        );

        return new self(
            $program_increment_tracker->getTrackerId(),
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
