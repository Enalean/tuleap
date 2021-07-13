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

namespace Tuleap\ProgramManagement\Stub;

use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTeam\BuildPotentialTeams;
use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTeam\PotentialTeam;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;

final class BuildPotentialTeamsStub implements BuildPotentialTeams
{
    /**
     * @var PotentialTeam[]
     */
    private array $potential_teams;

    /**
     * @param PotentialTeam[] $potential_teams
     */
    public function __construct(array $potential_teams)
    {
        $this->potential_teams = $potential_teams;
    }

    /**
     * @return PotentialTeam[]
     */
    public function buildPotentialTeams(ProgramForAdministrationIdentifier $program, \PFUser $user): array
    {
        return $this->potential_teams;
    }

    public static function buildValidPotentialTeamsFromId(PotentialTeam ...$potential_teams): self
    {
        return new self($potential_teams);
    }
}
