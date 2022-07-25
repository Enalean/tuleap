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
 */

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\TeamSynchronization;

use Tuleap\ProgramManagement\Domain\Events\TeamSynchronizationEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProcessProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\RetrieveLastChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\VerifyIsChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\SearchOpenProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MissingMirroredMilestoneCollection;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\SearchMirrorTimeboxesFromProgram;
use Tuleap\ProgramManagement\Domain\Team\SearchVisibleTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifier;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\LogMessage;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class MissingProgramIncrementCreator
{
    public function __construct(
        private SearchOpenProgramIncrements $search_open_program_increments,
        private SearchMirrorTimeboxesFromProgram $timebox_searcher,
        private VerifyIsProgramIncrement $program_increment_verifier,
        private VerifyIsVisibleArtifact $visibility_verifier,
        private RetrieveProgramIncrementTracker $tracker_retriever,
        private VerifyIsChangeset $verify_is_changeset,
        private RetrieveLastChangeset $retrieve_full_artifact,
        private ProcessProgramIncrementCreation $process_program_increment_creation,
        private SearchVisibleTeamsOfProgram $search_visible_teams_of_program,
        private BuildProgram $build_program,
    ) {
    }

    public function detectAndCreateMissingProgramIncrements(
        TeamSynchronizationEvent $event,
        UserIdentifier $user,
        ProjectReference $team,
        LogMessage $log_message,
    ): void {
        $open_program_increments = $this->search_open_program_increments->searchOpenProgramIncrements($event->getProgramId(), $user);
        $missing_milestones      = MissingMirroredMilestoneCollection::buildFromProgramIdentifierAndTeam($this->timebox_searcher, $open_program_increments, $team);
        if (! $missing_milestones) {
            return;
        }

        $log_message->debug("Missing milestones " . implode(',', $missing_milestones->missing_program_increments_ids));

        $program = ProgramIdentifier::fromId(
            $this->build_program,
            $event->getProgramId(),
            $user,
            null
        );

        foreach ($missing_milestones->missing_program_increments_ids as $missing_milestone_id) {
            $program_increment = ProgramIncrementIdentifier::fromId(
                $this->program_increment_verifier,
                $this->visibility_verifier,
                $missing_milestone_id,
                $user
            );

            $creation = ProgramIncrementCreation::fromTeamSynchronization(
                $this->tracker_retriever,
                $this->retrieve_full_artifact,
                $this->verify_is_changeset,
                $program_increment,
                $user
            );

            if (! $creation) {
                $log_message->error(
                    sprintf(
                        "Unable to build ProgramIncrementCreation from milestone %d in team %d of program %d.",
                        $missing_milestone_id,
                        $event->getTeamId(),
                        $event->getProgramId()
                    )
                );
                continue;
            }

            $team_identifier = TeamIdentifier::buildTeamOfProgramById(
                $this->search_visible_teams_of_program,
                $program,
                $user,
                $team->getId()
            );

            $this->process_program_increment_creation->processCreationForOneTeam($creation, $team_identifier);
        }
    }
}
