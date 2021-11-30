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

namespace Tuleap\ProgramManagement\Domain\Team;

use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I am the project identifier (id) of a Team project. My Team is always linked to at least one Program.
 * @see ProgramIdentifier
 * @psalm-immutable
 */
final class TeamIdentifier
{
    private function __construct(private int $id)
    {
    }

    /**
     * @return self[]
     * @throws ProgramHasNoTeamException
     * @throws TeamIsNotVisibleException
     */
    public static function buildCollectionFromProgram(
        SearchVisibleTeamsOfProgram $teams_searcher,
        ProgramIdentifier $program,
        UserIdentifier $user,
    ): array {
        $team_ids = $teams_searcher->searchTeamIdsOfProgram($program, $user);
        return array_map(static fn(int $id) => new self($id), $team_ids);
    }

    public function getId(): int
    {
        return $this->id;
    }
}
