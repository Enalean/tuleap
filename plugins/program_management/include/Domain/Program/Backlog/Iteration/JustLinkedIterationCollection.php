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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;

/**
 * I am a collection of IterationIdentifiers that have just been linked to a Program Increment.
 * They have never been linked to it before.
 * @see IterationIdentifier
 * @psalm-immutable
 */
final class JustLinkedIterationCollection
{
    public ProgramIncrementIdentifier $program_increment;
    /**
     * @var IterationIdentifier[]
     */
    public array $ids;

    private function __construct(ProgramIncrementIdentifier $program_increment, IterationIdentifier ...$ids)
    {
        $this->program_increment = $program_increment;
        $this->ids               = $ids;
    }

    public static function fromIterations(
        VerifyIterationHasBeenLinkedBefore $link_verifier,
        ProgramIncrementIdentifier $program_increment,
        IterationIdentifier ...$iterations,
    ): self {
        $iterations_that_have_never_been_linked_before = array_filter(
            $iterations,
            static fn(IterationIdentifier $iteration): bool => ! $link_verifier->hasIterationBeenLinkedBefore(
                $program_increment,
                $iteration
            )
        );
        return new self($program_increment, ...$iterations_that_have_never_been_linked_before);
    }

    public function isEmpty(): bool
    {
        return empty($this->ids);
    }
}
