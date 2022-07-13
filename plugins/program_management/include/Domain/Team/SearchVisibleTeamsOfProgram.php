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

interface SearchVisibleTeamsOfProgram
{
    /**
     * Returns non-empty array of int containing all team ids linked to $program.
     * All returned Teams are guaranteed visible by $user, otherwise it throws an Exception.
     * Throws an Exception if it finds no team for the given $program.
     * @throws ProgramHasNoTeamException
     * @throws TeamIsNotVisibleException
     */
    public function searchTeamIdsOfProgram(ProgramIdentifier $program, UserIdentifier $user): array;

    /**
     * Returns the $team_id when it belongs to the given $program.
     * It guarantees that the Team is visible by $user, otherwise it throws an Exception.
     * Throws an exception if the $team_id does not refer a project aggregated by the given $program.
     * @throws TeamIsNotAggregatedByProgramException
     * @throws TeamIsNotVisibleException
     */
    public function searchTeamWithIdInProgram(ProgramIdentifier $program, UserIdentifier $user, int $team_id): int;
}
