<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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
 *
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\VerifyIsSynchronizationPending;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\SearchOpenProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TeamSynchronization\VerifyTeamSynchronizationHasError;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MissingMirroredMilestoneCollection;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\SearchMirrorTimeboxesFromProgram;
use Tuleap\ProgramManagement\Domain\Team\SearchVisibleTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * @psalm-immutable
 */
final class ProgramAdminTeam
{
    public int $id;
    public string $public_name;
    public string $url;
    public string $project_icon;

    private function __construct(
        ProjectReference $team,
        public bool $should_synchronize_team,
        public bool $has_synchronization_pending,
        public bool $has_synchronization_error,
        public ?TrackerError $plannable_error,
        public ?TrackerError $increment_error,
        public ?TrackerError $iteration_error,
    ) {
        $this->id           = $team->getId();
        $this->public_name  = $team->getProjectLabel();
        $this->url          = $team->getUrl();
        $this->project_icon = $team->getProjectIcon();
    }

    /**
     * @return self[]
     */
    public static function build(
        SearchOpenProgramIncrements $search_open_program_increments,
        SearchMirrorTimeboxesFromProgram $timebox_searcher,
        ProgramForAdministrationIdentifier $admin_program,
        UserIdentifier $user_identifier,
        TeamProjectsCollection $team_collection,
        VerifyIsSynchronizationPending $verify_is_synchronization_pending,
        SearchVisibleTeamsOfProgram $team_searcher,
        VerifyTeamSynchronizationHasError $verify_team_synchronization_has_error,
        BuildProgram $build_program,
        ?TrackerError $plannable_error,
        ?TrackerError $increment_error,
        ?TrackerError $iteration_error,
    ): array {
        $teams_presenter = [];

        try {
            $open_program_increments = $search_open_program_increments->searchOpenProgramIncrements($admin_program->id, $user_identifier);
        } catch (ProjectIsNotAProgramException $e) {
            // when program has no team yet we don't need to check PI synchronisation
            $open_program_increments = [];
        }
        $teams_with_missing_milestones = MissingMirroredMilestoneCollection::buildCollectionFromProgramIdentifier($timebox_searcher, $open_program_increments, $team_collection->getTeamProjects());


        $teams_in_error = [];
        if ($plannable_error) {
            $teams_in_error = array_unique(array_merge($teams_in_error, $plannable_error->teams_with_error));
        }
        if ($increment_error) {
            $teams_in_error = array_unique(array_merge($teams_in_error, $increment_error->teams_with_error));
        }

        if ($iteration_error) {
            $teams_in_error = array_unique(array_merge($teams_in_error, $iteration_error->teams_with_error));
        }

        foreach ($team_collection->getTeamProjects() as $team) {
            $should_synchronize_team = ! in_array($team->getId(), $teams_in_error, true) && isset($teams_with_missing_milestones[$team->getId()]);
            $program_identifier      = ProgramIdentifier::fromId(
                $build_program,
                $admin_program->id,
                $user_identifier,
                null
            );
            $teams_presenter[]       = new self(
                $team,
                $should_synchronize_team,
                $verify_is_synchronization_pending->hasSynchronizationPending(
                    $program_identifier,
                    TeamIdentifier::buildTeamOfProgramById($team_searcher, $program_identifier, $user_identifier, $team->getId()),
                ),
                $verify_team_synchronization_has_error->hasASynchronizationError($program_identifier, $team),
                $plannable_error,
                $increment_error,
                $iteration_error
            );
        }

        return $teams_presenter;
    }
}
