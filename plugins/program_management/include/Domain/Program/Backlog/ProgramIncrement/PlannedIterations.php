<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationTrackerConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\BuildProgramBaseInfo;
use Tuleap\ProgramManagement\Domain\Workspace\BuildProgramFlags;
use Tuleap\ProgramManagement\Domain\Workspace\BuildProgramPrivacy;
use Tuleap\ProgramManagement\Domain\Workspace\ProgramBaseInfo;
use Tuleap\ProgramManagement\Domain\Workspace\ProgramFlag;
use Tuleap\ProgramManagement\Domain\Workspace\ProgramPrivacy;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserPreference;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyUserIsProgramAdmin;

/**
 * @psalm-immutable
 */
final class PlannedIterations
{
    /**
     * @param ProgramFlag[] $program_flags
     */
    private function __construct(
        private array $program_flags,
        private ProgramPrivacy $program_privacy,
        private ProgramBaseInfo $program_base_info,
        private ProgramIncrementInfo $program_increment_info,
        private bool $is_user_admin,
        private IterationLabels $iteration_labels,
        private IterationTrackerIdentifier $iteration_tracker,
        private bool $is_accessibility_mode_enabled,
    ) {
    }

    /**
     * @throws ProgramIncrementNotFoundException
     * @throws \Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException
     */
    public static function build(
        BuildProgramFlags $build_program_flags,
        BuildProgramPrivacy $build_program_privacy,
        BuildProgramBaseInfo $build_program_base_info,
        BuildProgramIncrementInfo $build_program_increment_info,
        VerifyUserIsProgramAdmin $verify_user_is_program_admin,
        ProgramIdentifier $program_identifier,
        UserIdentifier $user_identifier,
        ProgramIncrementIdentifier $increment_identifier,
        IterationTrackerConfiguration $iteration_configuration,
        UserPreference $user_preference_for_accessibility_mode,
    ): self {
        $program_flags     = $build_program_flags->build($program_identifier);
        $program_privacy   = $build_program_privacy->build($program_identifier);
        $program_base_info = $build_program_base_info->build($program_identifier);
        $program_increment = $build_program_increment_info->build($user_identifier, $increment_identifier);
        $is_user_admin     = $verify_user_is_program_admin->isUserProgramAdmin($user_identifier, $program_identifier);

        return new self(
            $program_flags,
            $program_privacy,
            $program_base_info,
            $program_increment,
            $is_user_admin,
            $iteration_configuration->labels,
            $iteration_configuration->iteration_tracker,
            (bool) $user_preference_for_accessibility_mode->getPreferenceValue()
        );
    }

    /**
     * @return ProgramFlag[]
     */
    public function getProgramFlag(): array
    {
        return $this->program_flags;
    }

    public function getProgramPrivacy(): ProgramPrivacy
    {
        return $this->program_privacy;
    }

    public function getProgramBaseInfo(): ProgramBaseInfo
    {
        return $this->program_base_info;
    }

    public function getProgramIncrementInfo(): ProgramIncrementInfo
    {
        return $this->program_increment_info;
    }

    public function isUserAdmin(): bool
    {
        return $this->is_user_admin;
    }

    public function getIterationLabels(): IterationLabels
    {
        return $this->iteration_labels;
    }

    public function getIterationTrackerId(): int
    {
        return $this->iteration_tracker->getId();
    }

    public function isAccessibilityModeEnabled(): bool
    {
        return $this->is_accessibility_mode_enabled;
    }
}
