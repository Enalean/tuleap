<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\RetrieveChangesetSubmissionDate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\RetrieveFieldValuesGatherer;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\RetrieveProgramOfIteration;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\TeamHasNoMirroredIterationTrackerException;
use Tuleap\ProgramManagement\Domain\Team\ProgramHasNoTeamException;
use Tuleap\ProgramManagement\Domain\Team\SearchVisibleTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifierCollection;
use Tuleap\ProgramManagement\Domain\Team\TeamIsNotVisibleException;
use Tuleap\ProgramManagement\Domain\Workspace\LogMessage;

final class IterationCreationProcessor implements ProcessIterationCreation
{
    public function __construct(
        private LogMessage $logger,
        private GatherSynchronizedFields $fields_gatherer,
        private RetrieveFieldValuesGatherer $values_retriever,
        private RetrieveChangesetSubmissionDate $submission_date_retriever,
        private RetrieveProgramOfIteration $program_retriever,
        private BuildProgram $program_builder,
        private SearchVisibleTeamsOfProgram $teams_searcher,
        private CreateIterations $iterations_creator,
    ) {
    }

    #[\Override]
    public function processCreation(IterationCreation $iteration_creation): void
    {
        $this->logger->debug(
            sprintf(
                'Processing iteration creation with iteration #%d for user #%d',
                $iteration_creation->getIteration()->getId(),
                $iteration_creation->getUser()->getId()
            )
        );
        try {
            $this->create($iteration_creation);
        } catch (
            FieldSynchronizationException
            | MirroredTimeboxReplicationException
            | ProgramAccessException
            | ProjectIsNotAProgramException
            | ProgramHasNoTeamException
            | TeamIsNotVisibleException $exception
        ) {
            $this->logger->error('Error during creation of mirror iterations', ['exception' => $exception]);
            return;
        }
    }

    #[\Override]
    public function processCreationForTeams(IterationCreation $iteration_creation, TeamIdentifierCollection $teams): void
    {
        $this->logger->debug(
            sprintf(
                'Processing iteration creation with iteration #%d for user #%d and for teams: %s',
                $iteration_creation->getIteration()->getId(),
                $iteration_creation->getUser()->getId(),
                implode(', ', $teams->getArrayOfTeamsId()),
            )
        );
        try {
            $this->createWithTeams($iteration_creation, $teams);
        } catch (
            FieldSynchronizationException
            | MirroredTimeboxReplicationException $exception
        ) {
            $this->logger->error('Error during creation of mirror iterations', ['exception' => $exception]);
            return;
        }
    }

    /**
     * @throws FieldSynchronizationException
     * @throws MirroredTimeboxReplicationException
     * @throws ProgramAccessException
     * @throws ProjectIsNotAProgramException
     * @throws ProgramHasNoTeamException
     * @throws TeamIsNotVisibleException
     */
    private function create(IterationCreation $creation): void
    {
        $program = ProgramIdentifier::fromIteration(
            $this->program_retriever,
            $this->program_builder,
            $creation->getIteration(),
            $creation->getUser()
        );

        $teams = TeamIdentifierCollection::fromProgram($this->teams_searcher, $program, $creation->getUser());
        $this->createWithTeams($creation, $teams);
    }

    /**
     * @throws TeamHasNoMirroredIterationTrackerException
     * @throws MirroredIterationCreationException
     * @throws MirroredTimeboxReplicationException
     * @throws FieldSynchronizationException
     */
    private function createWithTeams(IterationCreation $creation, TeamIdentifierCollection $teams): void
    {
        $source_values = SourceTimeboxChangesetValues::fromMirroringOrder(
            $this->fields_gatherer,
            $this->values_retriever,
            $this->submission_date_retriever,
            $creation
        );

        $this->logger->debug(self::class . ' create for #' . count($teams->getTeams()) . ' teams');
        $this->iterations_creator->createIterations($source_values, $teams, $creation);
    }
}
