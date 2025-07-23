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

use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Team\ProgramHasNoTeamException;
use Tuleap\ProgramManagement\Domain\Team\SearchVisibleTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Team\TeamIsNotAggregatedByProgramException;
use Tuleap\ProgramManagement\Domain\Team\TeamIsNotVisibleException;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class SearchVisibleTeamsOfProgramStub implements SearchVisibleTeamsOfProgram
{
    private function __construct(
        private array $team_ids,
        private bool $is_error_not_visible,
        private bool $has_no_team,
    ) {
    }

    /**
     * @no-named-arguments
     */
    public static function withTeamIds(int $team_id, int ...$other_team_ids): self
    {
        return new self([$team_id, ...$other_team_ids], false, false);
    }

    public static function withNotVisibleTeam(): self
    {
        return new self([], true, false);
    }

    public static function withNoTeam(): self
    {
        return new self([], false, true);
    }

    #[\Override]
    public function searchTeamIdsOfProgram(ProgramIdentifier $program, UserIdentifier $user): array
    {
        if ($this->has_no_team) {
            throw new ProgramHasNoTeamException($program);
        }
        if ($this->is_error_not_visible) {
            throw new TeamIsNotVisibleException($program, $user, 'project_name');
        }
        return $this->team_ids;
    }

    #[\Override]
    public function searchTeamWithIdInProgram(ProgramIdentifier $program, UserIdentifier $user, int $team_id): int
    {
        if ($this->has_no_team || ! in_array($team_id, $this->team_ids, true)) {
            throw new TeamIsNotAggregatedByProgramException($team_id, $program->getId());
        }
        if ($this->is_error_not_visible) {
            throw new TeamIsNotVisibleException($program, $user, 'project_name');
        }
        return $team_id;
    }
}
