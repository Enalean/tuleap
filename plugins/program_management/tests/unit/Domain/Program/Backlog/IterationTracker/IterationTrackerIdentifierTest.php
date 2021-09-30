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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker;

use Tuleap\ProgramManagement\Tests\Builder\IterationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveIterationTrackerStub;

final class IterationTrackerIdentifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ITERATION_TRACKER_ID = 99;

    public function testItBuildsFromIteration(): void
    {
        $iteration         = IterationIdentifierBuilder::buildWithId(237);
        $iteration_tracker = IterationTrackerIdentifier::fromIteration(
            RetrieveIterationTrackerStub::withValidTracker(self::ITERATION_TRACKER_ID),
            $iteration
        );
        self::assertSame(self::ITERATION_TRACKER_ID, $iteration_tracker->getId());
    }
}
