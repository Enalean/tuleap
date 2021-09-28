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

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Domain\Events\ProgramIncrementUpdateEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\VerifyIsIteration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyIsUser;

final class ProgramIncrementUpdateEventHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private SearchPendingIterations $iteration_searcher,
        private VerifyIsUser $user_verifier,
        private VerifyIsIteration $iteration_verifier,
        private VerifyIsVisibleArtifact $visibility_verifier,
        private VerifyIsProgramIncrement $program_increment_verifier,
        private VerifyIsChangeset $changeset_verifier,
        private DeletePendingIterations $iteration_deleter,
        private RetrieveProgramIncrementTracker $tracker_retriever,
        private BuildProgramIncrementUpdateProcessor $update_processor_builder,
        private ProcessIterationCreation $iteration_processor
    ) {
    }

    public function handle(?ProgramIncrementUpdateEvent $event): void
    {
        if (! $event) {
            return;
        }
        $this->buildAndProcessProgramIncrementUpdate($event);
        $pending_creations = $this->iteration_searcher->searchIterationCreationsByProgramIncrement(
            $event->getArtifactId(),
            $event->getUserId()
        );
        foreach ($pending_creations as $pending_creation) {
            $this->buildAndProcessIterationCreation($pending_creation);
        }
    }

    private function buildAndProcessProgramIncrementUpdate(ProgramIncrementUpdateEvent $event): void
    {
        $update = ProgramIncrementUpdate::fromProgramIncrementUpdateEvent(
            $this->user_verifier,
            $this->program_increment_verifier,
            $this->visibility_verifier,
            $this->changeset_verifier,
            $this->tracker_retriever,
            $event
        );
        if (! $update) {
            $this->logger->error(
                sprintf(
                    'Invalid data given in payload, skipping program increment update for artifact #%d, user #%d and changeset #%d',
                    $event->getArtifactId(),
                    $event->getUserId(),
                    $event->getChangesetId()
                )
            );
            return;
        }
        $processor = $this->update_processor_builder->getProcessor();
        $processor->processUpdate($update);
    }

    private function buildAndProcessIterationCreation(PendingIterationCreation $pending_creation): void
    {
        try {
            $iteration_creation = IterationCreation::fromPendingIterationCreation(
                $this->user_verifier,
                $this->iteration_verifier,
                $this->visibility_verifier,
                $this->program_increment_verifier,
                $this->changeset_verifier,
                $pending_creation
            );
        } catch (StoredIterationNoLongerValidException $e) {
            $iteration_id = $e->getIterationId();
            $this->logger->debug(
                sprintf('Stored iteration #%d is no longer valid, cleaning up pending iterations', $iteration_id)
            );
            $this->iteration_deleter->deletePendingIterationCreationsByIterationId($iteration_id);
            return;
        } catch (StoredProgramIncrementNoLongerValidException $e) {
            $program_increment_id = $e->getProgramIncrementId();
            $this->logger->debug(
                sprintf(
                    'Stored program increment #%d is no longer valid, cleaning up pending iterations',
                    $program_increment_id
                )
            );
            $this->iteration_deleter->deletePendingIterationCreationsByProgramIncrementId($program_increment_id);
            return;
        } catch (StoredChangesetNotFoundException | StoredUserNotFoundException $e) {
            $this->logger->error('Invalid data found in the database, skipping pending creation', ['exception' => $e]);
            return;
        }
        $this->iteration_processor->processIterationCreation($iteration_creation);
    }
}
