<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Plan\Inheritance;

use Tuleap\Option\Option;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewConfigurationTrackerIsValidCertificate;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\NewUserGroupThatCanPrioritizeIsValidCertificate;

/**
 * @psalm-immutable
 */
final readonly class ProgramInheritanceMapping
{
    /**
     * @param array<int, NewConfigurationTrackerIsValidCertificate>       $tracker_mapping
     * @param array<int, NewUserGroupThatCanPrioritizeIsValidCertificate> $user_group_mapping
     */
    public function __construct(
        public ProgramIdentifier $source_program,
        public ProgramForAdministrationIdentifier $new_program,
        private array $tracker_mapping,
        private array $user_group_mapping,
    ) {
    }

    /**
     * @return Option<NewConfigurationTrackerIsValidCertificate>
     */
    public function getMappedTrackerId(int $source_tracker_id): Option
    {
        if (isset($this->tracker_mapping[$source_tracker_id])) {
            return Option::fromValue($this->tracker_mapping[$source_tracker_id]);
        }
        return Option::nothing(NewConfigurationTrackerIsValidCertificate::class);
    }

    /**
     * @return Option<NewUserGroupThatCanPrioritizeIsValidCertificate>
     */
    public function getMappedUserGroupId(int $source_user_group_id): Option
    {
        if (isset($this->user_group_mapping[$source_user_group_id])) {
            return Option::fromValue($this->user_group_mapping[$source_user_group_id]);
        }
        return Option::nothing(NewUserGroupThatCanPrioritizeIsValidCertificate::class);
    }
}
