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

namespace Tuleap\ProgramManagement\Domain\Program\Plan;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;

/**
 * @psalm-immutable
 */
final class ProgramUserGroup
{
    private int $id;

    private function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * @throws InvalidProgramUserGroup
     */
    public static function buildProgramUserGroup(
        RetrieveProgramUserGroup $ugroup_retriever,
        string $raw_user_group_id,
        ProgramForAdministrationIdentifier $program,
    ): self {
        $user_group_id = $ugroup_retriever->getProjectUserGroupId($raw_user_group_id, $program);
        return new self($user_group_id);
    }

    public static function buildFake(): self
    {
        return new self(-1);
    }

    public function getId(): int
    {
        return $this->id;
    }
}
