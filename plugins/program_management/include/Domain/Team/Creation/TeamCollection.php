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

use Tuleap\ProgramManagement\Domain\Program\ToBeCreatedProgram;

/**
 * @psalm-immutable
 */
final class TeamCollection
{
    /**
     * @var Team[]
     */
    private $teams;
    /**
     * @var ToBeCreatedProgram
     */
    private $program;

    /**
     * @param Team[] $teams
     */
    public function __construct(array $teams, ToBeCreatedProgram $program)
    {
        $this->teams   = $teams;
        $this->program = $program;
    }

    /**
     * @return int[]
     */
    public function getTeamIds(): array
    {
        return array_map(
            static function (Team $team) {
                return $team->getTeamId();
            },
            $this->teams
        );
    }

    public function getProgram(): ToBeCreatedProgram
    {
        return $this->program;
    }
}
