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
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\JustLinkedIterationCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\VerifyIsIteration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\CheckProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Workspace\StoredUser;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyIsUser;

/**
 * I hold all the information necessary to create Mirrored Iterations from a source Iteration.
 * I can be stored and retrieved from storage.
 * @psalm-immutable
 */
final class IterationCreation
{
    public IterationIdentifier $iteration;
    public ProgramIncrementIdentifier $program_increment;
    public UserIdentifier $user;
    public ChangesetIdentifier $changeset;

    private function __construct(
        IterationIdentifier $iteration,
        ProgramIncrementIdentifier $program_increment,
        UserIdentifier $user,
        ChangesetIdentifier $changeset
    ) {
        $this->iteration         = $iteration;
        $this->program_increment = $program_increment;
        $this->user              = $user;
        $this->changeset         = $changeset;
    }

    /**
     * @return self[]
     */
    public static function buildCollectionFromJustLinkedIterations(
        RetrieveLastChangeset $changeset_retriever,
        LoggerInterface $logger,
        JustLinkedIterationCollection $iterations,
        UserIdentifier $user
    ): array {
        $creations = [];
        foreach ($iterations->ids as $iteration_identifier) {
            $last_changeset_id = DomainChangeset::fromIterationLastChangeset(
                $changeset_retriever,
                $iteration_identifier
            );
            if ($last_changeset_id === null) {
                $logger->error(
                    sprintf(
                        'Could not retrieve last changeset of iteration #%s, skipping it',
                        $iteration_identifier->id
                    ),
                );
                continue;
            }
            $creations[] = new self($iteration_identifier, $iterations->program_increment, $user, $last_changeset_id);
        }
        return $creations;
    }

    /**
     * @throws StoredIterationNoLongerValidException
     * @throws StoredProgramIncrementNoLongerValidException
     */
    public static function fromStorage(
        SearchPendingIteration $iteration_searcher,
        VerifyIsUser $user_verifier,
        VerifyIsIteration $iteration_verifier,
        VerifyIsVisibleArtifact $visibility_verifier,
        RetrieveUser $user_retriever,
        CheckProgramIncrement $program_increment_checker,
        VerifyIsChangeset $changeset_verifier,
        int $iteration_id,
        int $user_id
    ): ?self {
        $stored_creation = $iteration_searcher->searchPendingIterationCreation($iteration_id, $user_id);
        if (! $stored_creation) {
            return null;
        }
        $user_identifier = StoredUser::fromId($user_verifier, $stored_creation['user_id']);
        if (! $user_identifier) {
            return null;
        }
        $stored_iteration_id = $stored_creation['iteration_id'];
        $iteration           = IterationIdentifier::fromId(
            $iteration_verifier,
            $visibility_verifier,
            $stored_iteration_id,
            $user_identifier
        );
        if (! $iteration) {
            throw new StoredIterationNoLongerValidException($stored_iteration_id);
        }
        $user                 = $user_retriever->getUserWithId($user_identifier);
        $program_increment_id = $stored_creation['program_increment_id'];
        try {
            $program_increment = ProgramIncrementIdentifier::fromId(
                $program_increment_checker,
                $program_increment_id,
                $user
            );
        } catch (ProgramIncrementNotFoundException $e) {
            throw new StoredProgramIncrementNoLongerValidException($program_increment_id);
        }
        $changeset = DomainChangeset::fromId($changeset_verifier, $stored_creation['iteration_changeset_id']);
        if (! $changeset) {
            return null;
        }
        return new self($iteration, $program_increment, $user_identifier, $changeset);
    }
}
