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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\JustLinkedIterationCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\SearchIterations;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\VerifyIterationHasBeenLinkedBefore;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\LogMessage;

/**
 * I detect when new Iterations have been linked to a Program Increment for the first time, and I create
 * IterationCreations for each one.
 */
final class IterationCreationDetector
{
    public function __construct(
        private SearchIterations $iterations_searcher,
        private VerifyIsVisibleArtifact $visibility_verifier,
        private VerifyIterationHasBeenLinkedBefore $iteration_link_verifier,
        private LogMessage $logger,
        private RetrieveLastChangeset $changeset_retriever,
        private RetrieveIterationTracker $tracker_retriever,
    ) {
    }

    /**
     * @return IterationCreation[]
     */
    public function detectNewIterationCreations(ProgramIncrementUpdate $program_increment_update): array
    {
        $iterations             = IterationIdentifier::buildCollectionFromProgramIncrement(
            $this->iterations_searcher,
            $this->visibility_verifier,
            $program_increment_update->getProgramIncrement(),
            $program_increment_update->getUser()
        );
        $just_linked_iterations = JustLinkedIterationCollection::fromIterations(
            $this->iteration_link_verifier,
            $program_increment_update->getProgramIncrement(),
            ...$iterations
        );
        if ($just_linked_iterations->isEmpty()) {
            return [];
        }
        $this->logNewIterationIds($just_linked_iterations);
        return IterationCreation::buildCollectionFromJustLinkedIterations(
            $this->changeset_retriever,
            $this->tracker_retriever,
            $this->logger,
            $just_linked_iterations,
            $program_increment_update->getUser()
        );
    }

    private function logNewIterationIds(JustLinkedIterationCollection $just_linked_iterations): void
    {
        $ids        = array_map(
            static fn(IterationIdentifier $iteration): int => $iteration->getId(),
            $just_linked_iterations->ids
        );
        $ids_string = implode(',', $ids);
        $this->logger->debug("Program increment has new iterations: [$ids_string]");
    }
}
