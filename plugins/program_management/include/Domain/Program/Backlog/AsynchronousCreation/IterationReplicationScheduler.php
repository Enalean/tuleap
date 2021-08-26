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
use Tuleap\ProgramManagement\Domain\FeatureFlag\VerifyIterationsFeatureActive;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\JustLinkedIterationCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\SearchIterations;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\VerifyIterationHasBeenLinkedBefore;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;

/**
 * I detect when an Iteration needs to be replicated, store the pending replication
 * and schedule it.
 */
final class IterationReplicationScheduler
{
    public function __construct(
        private VerifyIterationsFeatureActive $feature_flag_verifier,
        private SearchIterations $iterations_searcher,
        private VerifyIsVisibleArtifact $visibility_verifier,
        private VerifyIterationHasBeenLinkedBefore $iteration_link_verifier,
        private LoggerInterface $logger,
        private RetrieveLastChangeset $changeset_retriever,
        private StorePendingIterations $pending_store,
        private RunIterationsCreation $iterations_creator
    ) {
    }

    public function replicateIterationsIfNeeded(ProgramIncrementUpdate $program_increment_update): void
    {
        if (! $this->feature_flag_verifier->isIterationsFeatureActive()) {
            return;
        }
        $iterations             = IterationIdentifier::buildCollectionFromProgramIncrement(
            $this->iterations_searcher,
            $this->visibility_verifier,
            $program_increment_update->program_increment,
            $program_increment_update->user
        );
        $just_linked_iterations = JustLinkedIterationCollection::fromIterations(
            $this->iteration_link_verifier,
            $program_increment_update->program_increment,
            ...$iterations
        );
        if ($just_linked_iterations->isEmpty()) {
            return;
        }
        $this->logNewIterationIds($just_linked_iterations);
        $creations = IterationCreation::buildCollectionFromJustLinkedIterations(
            $this->changeset_retriever,
            $this->logger,
            $just_linked_iterations,
            $program_increment_update->user
        );
        $this->pending_store->storePendingIterationCreations(...$creations);
        $this->iterations_creator->scheduleIterationCreations(...$creations);
    }

    private function logNewIterationIds(JustLinkedIterationCollection $just_linked_iterations): void
    {
        $ids        = array_map(
            static fn(IterationIdentifier $iteration): int => $iteration->id,
            $just_linked_iterations->ids
        );
        $ids_string = implode(',', $ids);
        $this->logger->debug("Program increment has new iterations: [$ids_string]");
    }
}
