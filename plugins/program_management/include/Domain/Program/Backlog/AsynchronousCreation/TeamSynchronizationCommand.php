<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * @psalm-immutable
 */
final class TeamSynchronizationCommand implements CommandTeamSynchronization
{
    private function __construct(
        private int $program_id,
        private int $team_id,
        private int $user_id,
    ) {
    }

    public static function fromProgramAndTeam(
        ProgramIdentifier $program,
        TeamIdentifier $team,
        UserIdentifier $user,
    ): self {
        return new self(
            $program->getId(),
            $team->getId(),
            $user->getId()
        );
    }

    #[\Override]
    public function getProgramId(): int
    {
        return $this->program_id;
    }

    #[\Override]
    public function getTeamId(): int
    {
        return $this->team_id;
    }

    #[\Override]
    public function getUserId(): int
    {
        return $this->user_id;
    }
}
