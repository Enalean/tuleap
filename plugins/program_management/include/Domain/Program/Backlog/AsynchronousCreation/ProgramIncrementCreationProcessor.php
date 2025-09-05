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
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlanProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlanUserStoriesInMirroredProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\RetrieveProgramOfProgramIncrement;
use Tuleap\ProgramManagement\Domain\Team\ProgramHasNoTeamException;
use Tuleap\ProgramManagement\Domain\Team\SearchVisibleTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifier;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifierCollection;
use Tuleap\ProgramManagement\Domain\Team\TeamIsNotVisibleException;
use Tuleap\ProgramManagement\Domain\Workspace\LogMessage;

final class ProgramIncrementCreationProcessor implements ProcessProgramIncrementCreation
{
    public function __construct(
        private LogMessage $logger,
        private PlanUserStoriesInMirroredProgramIncrements $user_stories_planner,
        private SearchVisibleTeamsOfProgram $teams_searcher,
        private RetrieveProgramOfProgramIncrement $program_retriever,
        private BuildProgram $program_builder,
        private PlanProgramIncrements $plan_program_increment,
    ) {
    }

    #[\Override]
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

    /**
     * @throws UserStoryPlanException
     * @throws ProgramAccessException
     * @throws ProjectIsNotAProgramException
     * @throws FieldSynchronizationException
     * @throws TeamIsNotVisibleException
     * @throws MirroredTimeboxReplicationException
     * @throws ProgramHasNoTeamException
     */
    #[\Override]
    public function synchronizeProgramIncrementAndIterationsForTeam(ProgramIncrementCreation $creation, TeamIdentifier $team): void
    {
        $this->logger->debug(
            sprintf(
                'Processing program increment creation with program increment #%d for user #%d for team #%d',
                $creation->getProgramIncrement()->getId(),
                $creation->getUser()->getId(),
                $team->getId()
            )
        );
        $this->createForATeam($creation, $team);
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

        $teams       = TeamIdentifierCollection::fromProgram($this->teams_searcher, $program, $user);
        $plan_change = $this->plan_program_increment->createProgramIncrementAndReturnPlanChange($creation, $teams);
        $this->user_stories_planner->plan($plan_change);
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
        $teams       = TeamIdentifierCollection::fromSingleTeam($team);
        $plan_change = $this->plan_program_increment->createProgramIncrementAndReturnPlanChange($creation, $teams);
        $this->user_stories_planner->planForATeam($plan_change, $team);
    }
}
