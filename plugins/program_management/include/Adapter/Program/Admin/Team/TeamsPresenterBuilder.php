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

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\SearchOpenProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredMilestoneCollection;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\SearchMirrorTimeboxesFromProgram;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * @psalm-immutable
 */
final class TeamsPresenterBuilder
{
    /**
     * @params int[] $teams_in_error
     * @return TeamPresenter[]
     */
    public static function buildTeamsPresenter(
        SearchOpenProgramIncrements $search_open_program_increments,
        SearchMirrorTimeboxesFromProgram $timebox_searcher,
        ProgramForAdministrationIdentifier $admin_program,
        UserIdentifier $user_identifier,
        TeamProjectsCollection $team_collection,
        array $teams_in_error,
    ): array {
        $teams_presenter = [];

        $open_program_increments       = $search_open_program_increments->searchOpenProgramIncrements($admin_program->id, $user_identifier);
        $teams_with_missing_milestones = MirroredMilestoneCollection::buildCollectionFromProgramIdentifier($timebox_searcher, $open_program_increments, $team_collection->getTeamProjects());

        foreach ($team_collection->getTeamProjects() as $team) {
            $should_synchronize_team = ! in_array($team->getId(), $teams_in_error, true) && isset($teams_with_missing_milestones[$team->getId()]);
            $teams_presenter[]       = new TeamPresenter($team, $should_synchronize_team);
        }

        return $teams_presenter;
    }
}
