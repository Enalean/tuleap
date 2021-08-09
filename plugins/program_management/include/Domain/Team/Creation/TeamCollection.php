<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Team\Creation;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;

/**
 * @psalm-immutable
 */
final class TeamCollection
{
    /**
     * @var Team[]
     */
    private array $teams;
    private ProgramForAdministrationIdentifier $program;

    /**
     * @param Team[] $teams
     */
    private function __construct(array $teams, ProgramForAdministrationIdentifier $program)
    {
        $this->teams   = $teams;
        $this->program = $program;
    }

    /**
     * @return int[]
     */
    public function getTeamIds(): array
    {
        return array_map(static fn(Team $team): int => $team->getTeamId(), $this->teams);
    }

    public function getProgram(): ProgramForAdministrationIdentifier
    {
        return $this->program;
    }

    public static function fromProgramAndTeams(ProgramForAdministrationIdentifier $program, Team ...$teams): self
    {
        return new self($teams, $program);
    }
}
