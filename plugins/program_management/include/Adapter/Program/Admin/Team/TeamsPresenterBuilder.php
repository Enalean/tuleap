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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\Team;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;

/**
 * @psalm-immutable
 */
final class TeamsPresenterBuilder
{
    /**
     * @params int[] $teams_in_error
     * @return TeamPresenter[]
     */
    public static function buildTeamsPresenter(TeamProjectsCollection $team_collection, array $teams_in_error): array
    {
        $teams_presenter = [];

        foreach ($team_collection->getTeamProjects() as $team) {
            $teams_presenter[] = new TeamPresenter($team, in_array($team->getId(), $teams_in_error, true));
        }

        return $teams_presenter;
    }
}
