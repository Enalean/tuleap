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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\VerifyIterationHasBeenLinkedBefore;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;

final class VerifyIterationHasBeenLinkedBeforeStub implements VerifyIterationHasBeenLinkedBefore
{
    /**
     * @param int[] $iterations_that_have_been_linked_before
     */
    private function __construct(private array $iterations_that_have_been_linked_before)
    {
    }

    #[\Override]
    public function hasIterationBeenLinkedBefore(
        ProgramIncrementIdentifier $program_increment,
        IterationIdentifier $iteration,
    ): bool {
        return in_array($iteration->getId(), $this->iterations_that_have_been_linked_before, true);
    }

    /**
     * @no-named-arguments
     */
    public static function withIterationIds(int $iteration_that_has_been_linked_before_id, int ...$other_ids): self
    {
        return new self([$iteration_that_has_been_linked_before_id, ...$other_ids]);
    }

    public static function withNoIteration(): self
    {
        return new self([]);
    }
}
