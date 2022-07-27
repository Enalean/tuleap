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
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\VerifyIsSynchronizationPending;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\SearchOpenProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MissingMirroredMilestoneCollection;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\SearchMirrorTimeboxesFromProgram;
use Tuleap\ProgramManagement\Domain\Team\SearchVisibleTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifier;
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
        VerifyIsSynchronizationPending $verify_is_synchronization_pending,
        SearchVisibleTeamsOfProgram $team_searcher,
        BuildProgram $build_program,
        array $teams_in_error,
    ): array {
        $teams_presenter = [];

        try {
            $open_program_increments = $search_open_program_increments->searchOpenProgramIncrements($admin_program->id, $user_identifier);
        } catch (ProjectIsNotAProgramException $e) {
            // when program has no team yet we don't need to check PI synchronisation
            $open_program_increments = [];
        }
        $teams_with_missing_milestones = MissingMirroredMilestoneCollection::buildCollectionFromProgramIdentifier($timebox_searcher, $open_program_increments, $team_collection->getTeamProjects());

        foreach ($team_collection->getTeamProjects() as $team) {
            $should_synchronize_team = ! in_array($team->getId(), $teams_in_error, true) && isset($teams_with_missing_milestones[$team->getId()]);
            $program_identifier      = ProgramIdentifier::fromId(
                $build_program,
                $admin_program->id,
                $user_identifier,
                null
            );
            $teams_presenter[]       = new TeamPresenter(
                $team,
                $should_synchronize_team,
                $verify_is_synchronization_pending->hasSynchronizationPending(
                    $program_identifier,
                    TeamIdentifier::buildTeamOfProgramById($team_searcher, $program_identifier, $user_identifier, $team->getId()),
                )
            );
        }

        return $teams_presenter;
    }
}
