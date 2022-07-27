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

namespace Tuleap\ProgramManagement\Tests\Builder;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\JustLinkedIterationCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIterationHasBeenLinkedBeforeStub;

final class JustLinkedIterationCollectionBuilder
{
    /**
     * @param $with_iterations array{id: int, changeset_id: int}[]
     */
    public static function buildWithProgramIncrementAndIterationIds(
        ProgramIncrementIdentifier $program_increment,
        array $with_iterations,
    ): JustLinkedIterationCollection {
        $iterations = IterationIdentifierCollectionBuilder::buildWithIterations($with_iterations);
        return JustLinkedIterationCollection::fromIterations(
            VerifyIterationHasBeenLinkedBeforeStub::withNoIteration(),
            $program_increment,
            $iterations
        );
    }
}
