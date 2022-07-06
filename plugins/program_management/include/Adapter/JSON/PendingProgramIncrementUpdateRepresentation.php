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

namespace Tuleap\ProgramManagement\Adapter\JSON;

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;

/**
 * I represent a pending Program Increment creation in JSON format
 * @psalm-immutable
 */
final class PendingProgramIncrementUpdateRepresentation
{
    /**
     * @var PendingIterationCreationRepresentation[]
     */
    public array $iterations;

    private function __construct(
        public int $program_increment_id,
        public int $user_id,
        public int $changeset_id,
        public int $old_changeset_id,
        PendingIterationCreationRepresentation ...$iterations,
    ) {
        $this->iterations = $iterations;
    }

    public static function fromUpdateAndCreations(ProgramIncrementUpdate $update, IterationCreation ...$creations): self
    {
        $iterations = array_map(
            static fn(IterationCreation $creation) => PendingIterationCreationRepresentation::fromIterationCreation(
                $creation
            ),
            $creations
        );
        return new self(
            $update->getProgramIncrement()->getId(),
            $update->getUser()->getId(),
            $update->getChangeset()->getId(),
            $update->getOldChangeset()->getId(),
            ...$iterations
        );
    }
}
