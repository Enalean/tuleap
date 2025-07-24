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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\CommandTeamSynchronization;

final class CommandTeamSynchronizationStub implements CommandTeamSynchronization
{
    private function __construct(
        private int $program_id,
        private int $team_id,
        private int $user_id,
    ) {
    }

    public static function withProgramAndTeamIdsAndUserId(int $program_id, int $team_id, int $user_id): self
    {
        return new self(
            $program_id,
            $team_id,
            $user_id
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
