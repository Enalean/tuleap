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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\UserStoryPlanException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlanUserStoriesInMirroredProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\ProgramIncrementChanged;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\RetrieveChangesetSubmissionDate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\RetrieveFieldValuesGatherer;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\RetrieveProgramOfProgramIncrement;
use Tuleap\ProgramManagement\Domain\RetrieveProjectReference;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredProgramIncrementTrackerIdentifierCollection;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveMirroredProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\TeamHasNoMirroredProgramIncrementTrackerException;
use Tuleap\ProgramManagement\Domain\Team\ProgramHasNoTeamException;
use Tuleap\ProgramManagement\Domain\Team\SearchVisibleTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifier;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifierCollection;
use Tuleap\ProgramManagement\Domain\Team\TeamIsNotVisibleException;
use Tuleap\ProgramManagement\Domain\Workspace\LogMessage;

final class ProgramIncrementCreationProcessor implements ProcessProgramIncrementCreation
{
    public function __construct(
        private RetrieveMirroredProgramIncrementTracker $mirrored_tracker_retriever,
        private CreateProgramIncrements $program_increment_creator,
        private LogMessage $logger,
        private PlanUserStoriesInMirroredProgramIncrements $user_stories_planner,
        private SearchVisibleTeamsOfProgram $teams_searcher,
        private RetrieveProjectReference $project_retriever,
        private GatherSynchronizedFields $fields_gatherer,
        private RetrieveFieldValuesGatherer $values_retriever,
        private RetrieveChangesetSubmissionDate $submission_date_retriever,
        private RetrieveProgramOfProgramIncrement $program_retriever,
        private BuildProgram $program_builder,
    ) {
    }

    public function processCreation(ProgramIncrementCreation $creation): void
    {
        $this->logger->debug(
            sprintf(
                'Processing program increment creation with program increment #%d for user #%d',
                $creation->getProgramIncrement()->getId(),
                $creation->getUser()->getId()
            )
        );
        try {
            $this->createInAllTeams($creation);
        } catch (
            FieldSynchronizationException
            | MirroredTimeboxReplicationException
            | ProgramAccessException
            | ProjectIsNotAProgramException
            | ProgramHasNoTeamException
            | TeamIsNotVisibleException $exception
        ) {
            $this->logger->error('Error during creation of mirror program increments', ['exception' => $exception]);
        } catch (UserStoryPlanException $exception) {
            $this->logger->error(
                'Error during planning of user stories in mirror program increments',
                ['exception' => $exception]
            );
        }
    }

    public function processCreationForOneTeam(ProgramIncrementCreation $creation, TeamIdentifier $team): void
    {
        $this->logger->debug(
            sprintf(
                'Processing program increment creation with program increment #%d for user #%d for team #%d',
                $creation->getProgramIncrement()->getId(),
                $creation->getUser()->getId(),
                $team->getId()
            )
        );
        try {
            $this->createForATeam($creation, $team);
        } catch (
            FieldSynchronizationException
            | MirroredTimeboxReplicationException
            | ProgramAccessException
            | ProjectIsNotAProgramException
            | ProgramHasNoTeamException
            | TeamIsNotVisibleException $exception
        ) {
            $this->logger->error('Error during creation of mirror program increments for one team', ['exception' => $exception]);
        } catch (UserStoryPlanException $exception) {
            $this->logger->error(
                'Error during planning of user stories in mirror program increments for one team',
                ['exception' => $exception]
            );
        }
    }

    /**
     * @throws FieldSynchronizationException
     * @throws MirroredTimeboxReplicationException
     * @throws ProgramAccessException
     * @throws ProjectIsNotAProgramException
     * @throws ProgramHasNoTeamException
     * @throws TeamIsNotVisibleException
     * @throws UserStoryPlanException
     */
    private function createInAllTeams(ProgramIncrementCreation $creation): void
    {
        $user    = $creation->getUser();
        $program = ProgramIdentifier::fromProgramIncrement(
            $this->program_retriever,
            $this->program_builder,
            $creation->getProgramIncrement(),
            $user
        );

        $teams                     = TeamIdentifierCollection::fromProgram(
            $this->teams_searcher,
            $program,
            $user
        );
        $program_increment_changed = $this->getPlanChanged($creation, $teams);
        $this->user_stories_planner->plan($program_increment_changed);
    }

    /**
     * @throws FieldSynchronizationException
     * @throws MirroredTimeboxReplicationException
     * @throws ProgramAccessException
     * @throws ProjectIsNotAProgramException
     * @throws ProgramHasNoTeamException
     * @throws TeamIsNotVisibleException
     * @throws UserStoryPlanException
     */
    private function createForATeam(ProgramIncrementCreation $creation, TeamIdentifier $team): void
    {
        $teams                     = TeamIdentifierCollection::fromSingleTeam($team);
        $program_increment_changed = $this->getPlanChanged($creation, $teams);
        $this->user_stories_planner->planForATeam($program_increment_changed, $team);
    }

    /**
     * @throws UserStoryPlanException
     * @throws TeamHasNoMirroredProgramIncrementTrackerException
     * @throws MirroredTimeboxReplicationException
     * @throws FieldSynchronizationException
     */
    private function getPlanChanged(ProgramIncrementCreation $creation, TeamIdentifierCollection $teams): ProgramIncrementChanged
    {
        $source_values = SourceTimeboxChangesetValues::fromMirroringOrder(
            $this->fields_gatherer,
            $this->values_retriever,
            $this->submission_date_retriever,
            $creation
        );

        $user = $creation->getUser();

        $mirrored_trackers = MirroredProgramIncrementTrackerIdentifierCollection::fromTeams(
            $this->mirrored_tracker_retriever,
            $this->project_retriever,
            $teams,
            $user
        );

        $this->program_increment_creator->createProgramIncrements(
            $source_values,
            $mirrored_trackers,
            $user
        );

        return ProgramIncrementChanged::fromCreation($creation);
    }
}
