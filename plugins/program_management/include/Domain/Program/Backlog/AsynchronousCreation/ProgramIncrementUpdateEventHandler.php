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

final class ProgramIncrementUpdateEventHandler implements ProcessProgramIncrementUpdate, ProcessIterationCreation
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
        private SearchPendingProgramIncrementUpdates $update_searcher,
        private RetrieveProgramIncrementTracker $tracker_retriever,
        private DeletePendingProgramIncrementUpdates $pending_update_deleter
    ) {
    }

    public function handle(?ProgramIncrementUpdateEvent $event): void
    {
        if (! $event) {
            return;
        }
        $pending_update = $this->update_searcher->searchUpdate($event->getArtifactId(), $event->getUserId());
        if ($pending_update) {
            $this->buildAndProcessProgramIncrementUpdate($pending_update);
        }
        $pending_creations = $this->iteration_searcher->searchIterationCreationsByProgramIncrement(
            $event->getArtifactId(),
            $event->getUserId()
        );
        foreach ($pending_creations as $pending_creation) {
            $this->buildAndProcessIterationCreation($pending_creation);
        }
    }

    private function buildAndProcessProgramIncrementUpdate(PendingProgramIncrementUpdate $pending_update): void
    {
        try {
            $update = ProgramIncrementUpdate::fromPendingUpdate(
                $this->user_verifier,
                $this->program_increment_verifier,
                $this->visibility_verifier,
                $this->changeset_verifier,
                $this->tracker_retriever,
                $pending_update
            );
        } catch (StoredProgramIncrementNoLongerValidException $e) {
            $program_increment_id = $e->getProgramIncrementId();
            $this->logger->debug(
                sprintf('Stored program increment #%d is no longer valid, cleaning up pending update', $program_increment_id)
            );
            $this->pending_update_deleter->deletePendingProgramIncrementUpdatesByProgramIncrementId($program_increment_id);
            return;
        } catch (StoredChangesetNotFoundException | StoredUserNotFoundException $e) {
            $this->logger->error('Invalid data found in the database, skipping pending update', ['exception' => $e]);
            return;
        }
        $this->processProgramIncrementUpdate($update);
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
        }
        if (! $iteration_creation) {
            return;
        }
        $this->processIterationCreation($iteration_creation);
    }

    public function processProgramIncrementUpdate(ProgramIncrementUpdate $update): void
    {
        $program_increment_id = $update->program_increment->getId();
        $user_id              = $update->user->getId();
        $this->logger->debug(
            "Processing program increment update with program increment #$program_increment_id for user #$user_id"
        );
    }

    public function processIterationCreation(IterationCreation $iteration_creation): void
    {
        $iteration_id = $iteration_creation->iteration->id;
        $user_id      = $iteration_creation->user->getId();
        $this->logger->debug("Processing iteration creation with iteration #$iteration_id for user #$user_id");
    }
}
