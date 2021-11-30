<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Admin;

use Tuleap\ProgramManagement\Domain\Program\Plan\InvalidProgramUserGroup;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramUserGroup;
use Tuleap\ProgramManagement\Domain\Program\Plan\RetrieveProgramUserGroup;

/**
 * I am a collection of user group identifiers.
 * @psalm-immutable
 */
final class ProgramUserGroupCollection
{
    /**
     * @var ProgramUserGroup[]
     */
    private array $user_group_identifiers;

    private function __construct(array $user_group_identifiers)
    {
        $this->user_group_identifiers = $user_group_identifiers;
    }

    /**
     * @param non-empty-list<string> $raw_user_group_ids
     * @throws InvalidProgramUserGroup
     */
    public static function fromRawIdentifiers(
        RetrieveProgramUserGroup $user_group_builder,
        ProgramForAdministrationIdentifier $program,
        array $raw_user_group_ids,
    ): self {
        $program_user_groups = [];

        foreach ($raw_user_group_ids as $raw_user_group_id) {
            $program_user_groups[] = ProgramUserGroup::buildProgramUserGroup(
                $user_group_builder,
                $raw_user_group_id,
                $program
            );
        }

        return new self($program_user_groups);
    }

    public function getUserGroups(): array
    {
        return $this->user_group_identifiers;
    }
}
