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
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I detect when an Iteration needs to be replicated and schedule the asynchronous task
 */
final class IterationReplicationScheduler
{
    private VerifyIterationsFeatureActive $feature_flag_verifier;
    private SearchIterations $iterations_searcher;
    private VerifyIsVisibleArtifact $visibility_verifier;
    private VerifyIterationHasBeenLinkedBefore $iteration_link_verifier;
    private LoggerInterface $logger;

    public function __construct(
        VerifyIterationsFeatureActive $feature_flag_verifier,
        SearchIterations $iterations_searcher,
        VerifyIsVisibleArtifact $visibility_verifier,
        VerifyIterationHasBeenLinkedBefore $iteration_link_verifier,
        LoggerInterface $logger
    ) {
        $this->feature_flag_verifier   = $feature_flag_verifier;
        $this->iterations_searcher     = $iterations_searcher;
        $this->visibility_verifier     = $visibility_verifier;
        $this->iteration_link_verifier = $iteration_link_verifier;
        $this->logger                  = $logger;
    }

    public function replicateIterationsIfNeeded(
        ProgramIncrementIdentifier $program_increment,
        UserIdentifier $user
    ): void {
        if (! $this->feature_flag_verifier->isIterationsFeatureActive()) {
            return;
        }
        $iterations             = IterationIdentifier::buildCollectionFromProgramIncrement(
            $this->iterations_searcher,
            $this->visibility_verifier,
            $program_increment,
            $user
        );
        $just_linked_iterations = JustLinkedIterationCollection::fromIterations(
            $this->iteration_link_verifier,
            $program_increment,
            ...$iterations
        );
        if ($just_linked_iterations->isEmpty()) {
            return;
        }
        $ids        = array_map(
            static fn(IterationIdentifier $iteration): int => $iteration->id,
            $just_linked_iterations->ids
        );
        $ids_string = implode(',', $ids);
        $this->logger->debug("Program increment has new iterations: [$ids_string]");
    }
}
