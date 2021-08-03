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
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I store the information necessary to create Mirrored Iterations from a source Iteration.
 * I will be stored in DB.
 * @psalm-immutable
 */
final class NewPendingIterationCreation
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
            $last_changeset_id = ChangesetIdentifier::fromIterationLastChangeset(
                $changeset_retriever,
                $iteration_identifier
            );
            if ($last_changeset_id === null) {
                $logger->error(
                    sprintf('Could not retrieve last changeset of iteration #%s, skipping it', $iteration_identifier->id),
                );
                continue;
            }
            $creations[] = new self($iteration_identifier, $iterations->program_increment, $user, $last_changeset_id);
        }
        return $creations;
    }
}
