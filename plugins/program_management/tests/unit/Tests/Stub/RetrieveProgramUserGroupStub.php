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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\RetrieveProgramUserGroup;
use Tuleap\ProgramManagement\Domain\Program\ProgramUserGroupDoesNotExistException;

final class RetrieveProgramUserGroupStub implements RetrieveProgramUserGroup
{
    /**
     * @param list<int> $user_group_ids
     */
    private function __construct(private array $user_group_ids)
    {
    }

    #[\Override]
    public function getProjectUserGroupId(string $raw_user_group_id, ProgramForAdministrationIdentifier $program): int
    {
        if (count($this->user_group_ids) > 0) {
            return array_shift($this->user_group_ids);
        }
        throw new ProgramUserGroupDoesNotExistException($raw_user_group_id);
    }

    /**
     * @no-named-arguments
     */
    public static function withValidUserGroups(int $user_group_id, int ...$other_user_group_ids): self
    {
        return new self([$user_group_id, ...$other_user_group_ids]);
    }

    public static function withNotValidUserGroup(): self
    {
        return new self([]);
    }
}
