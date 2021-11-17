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
use Tuleap\ProgramManagement\Tests\Stub\TrackerIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationTrackerStub;

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

    public function testItReturnsNullIfTheGivenTrackerIsNotAnIterationTracker(): void
    {
        $tracker                    = TrackerIdentifierStub::buildWithDefault();
        $iteration_tracker_verifier = VerifyIsIterationTrackerStub::buildNotIteration();

        $iteration = IterationTrackerIdentifier::fromTrackerIdentifier(
            $iteration_tracker_verifier,
            $tracker
        );

        self::assertNull($iteration);
    }

    public function testItReturnsTheIdentifierIfTheGivenTrackerIsAnIterationTracker(): void
    {
        $tracker                    = TrackerIdentifierStub::withId(self::ITERATION_TRACKER_ID);
        $iteration_tracker_verifier = VerifyIsIterationTrackerStub::buildValidIteration();

        $iteration = IterationTrackerIdentifier::fromTrackerIdentifier(
            $iteration_tracker_verifier,
            $tracker
        );

        self::assertNotNull($iteration);
        self::assertSame(self::ITERATION_TRACKER_ID, $iteration->getId());
    }
}
