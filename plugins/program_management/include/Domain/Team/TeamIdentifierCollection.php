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
 * @psalm-immutable
 */
final class TeamIdentifierCollection
{
    /**
     * @param TeamIdentifier[] $teams
     */
    private function __construct(private array $teams)
    {
    }

    /**
     * @throws ProgramHasNoTeamException
     * @throws TeamIsNotVisibleException
     */
    public static function fromProgram(
        SearchVisibleTeamsOfProgram $teams_searcher,
        ProgramIdentifier $program,
        UserIdentifier $user,
    ): self {
        $teams = TeamIdentifier::buildCollectionFromProgram($teams_searcher, $program, $user);
        return new self($teams);
    }

    /**
     * @return TeamIdentifier[]
     */
    public function getTeams(): array
    {
        return $this->teams;
    }
}
