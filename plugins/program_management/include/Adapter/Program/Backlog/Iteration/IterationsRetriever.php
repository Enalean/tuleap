<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\Iteration;

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\SearchIterations;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrement;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\REST\v1\IterationRepresentation;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

final class IterationsRetriever
{
    public function __construct(
        private VerifyIsProgramIncrement $verify_is_program_increment,
        private VerifyIsVisibleArtifact $verify_is_visible_artifact,
        private SearchIterations $search_iterations,
        private \Tracker_ArtifactFactory $artifact_factory,
        private SemanticTimeframeBuilder $semantic_timeframe_builder,
        private RetrieveUser $retrieve_user,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @return IterationRepresentation[]
     * @throws ProgramIncrementNotFoundException
     */
    public function retrieveIterations(int $program_increment_id, UserIdentifier $user_identifier): array
    {
        $program_increment = ProgramIncrementIdentifier::fromId(
            $this->verify_is_program_increment,
            $this->verify_is_visible_artifact,
            $program_increment_id,
            $user_identifier
        );

        $user       = $this->retrieve_user->getUserWithId($user_identifier);
        $iterations = IterationIdentifier::buildCollectionFromProgramIncrement(
            $this->search_iterations,
            $this->verify_is_visible_artifact,
            $program_increment,
            $user_identifier
        );

        $representations = [];
        foreach ($iterations as $iteration) {
            $iteration_artifact = $this->artifact_factory->getArtifactById($iteration->getId());
            if (! $iteration_artifact) {
                continue;
            }

            $iteration_representation = IterationRepresentation::buildFromArtifact(
                $this->semantic_timeframe_builder,
                $this->logger,
                $iteration_artifact,
                $user
            );

            if (! $iteration_representation) {
                continue;
            }

            $representations[] = $iteration_representation;
        }

        return $representations;
    }
}
