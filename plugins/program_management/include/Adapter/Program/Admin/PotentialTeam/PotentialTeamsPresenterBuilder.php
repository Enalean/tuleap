<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\PotentialTeam;

use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTeam\PotentialTeam;

/**
 * @psalm-immutable
 */
final class PotentialTeamsPresenterBuilder
{
    /**
     * @param PotentialTeam[] $potential_teams
     * @return PotentialTeamPresenter[]
     */
    public static function buildPotentialTeamsPresenter(array $potential_teams): array
    {
        $teams_presenter = [];

        foreach ($potential_teams as $potential_team) {
            $teams_presenter[] = new PotentialTeamPresenter($potential_team);
        }

        return $teams_presenter;
    }
}
