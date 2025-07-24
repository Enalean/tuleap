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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\SearchTeamsOfProgram;

final class SearchTeamsOfProgramStub implements SearchTeamsOfProgram
{
    /**
     * @param int[] $team_ids
     */
    private function __construct(private array $team_ids)
    {
    }

    #[\Override]
    public function searchTeamIdsOfProgram(int $project_id): array
    {
        return $this->team_ids;
    }

    /**
     * @no-named-arguments
     */
    public static function withTeamIds(int $team_id, int ...$other_team_ids): self
    {
        return new self([$team_id, ...$other_team_ids]);
    }

    public static function withNoTeams(): self
    {
        return new self([]);
    }
}
