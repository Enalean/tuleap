<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration;

use Tuleap\ProgramManagement\Tests\Stub\ArtifactUpdatedEventStub;
use Tuleap\ProgramManagement\Tests\Stub\IterationUpdateEventStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationTrackerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class IterationUpdateTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ITERATION_ID         = 10;
    private const ITERATION_TRACKER_ID = 25;
    private const USER_ID              = 118;
    private const CHANGESET_ID         = 3001;
    private const OLD_CHANGESET_ID     = 3000;

    private ArtifactUpdatedEventStub $event;


    protected function setUp(): void
    {
        $this->event = ArtifactUpdatedEventStub::withIds(
            self::ITERATION_ID,
            self::ITERATION_TRACKER_ID,
            self::USER_ID,
            self::CHANGESET_ID,
            self::OLD_CHANGESET_ID
        );
    }

    public function testItReturnsNullWhenArtifactUpdatedIsNotAnIteration(): void
    {
        $iteration_tracker_verifier = VerifyIsIterationTrackerStub::buildNotIteration();

        $expected_iteration_update = IterationUpdate::fromArtifactUpdateEvent($iteration_tracker_verifier, $this->event);

        self::assertNull($expected_iteration_update);
    }

    public function testItReturnsTheUpdateIfTheGivenIterationFromUpdateEventIsAnIteration(): void
    {
        $iteration_tracker_verifier = VerifyIsIterationTrackerStub::buildValidIteration();

        $iteration_update = IterationUpdate::fromArtifactUpdateEvent($iteration_tracker_verifier, $this->event);

        self::assertNotNull($iteration_update);
        self::assertSame(self::ITERATION_ID, $iteration_update->getTimebox()->getId());
        self::assertSame(self::ITERATION_ID, $iteration_update->getIteration()->getId());
        self::assertSame(self::ITERATION_TRACKER_ID, $iteration_update->getTracker()->getId());
        self::assertSame(self::USER_ID, $iteration_update->getUser()->getId());
        self::assertSame(self::CHANGESET_ID, $iteration_update->getChangeset()->getId());
    }

    public function testItBuildsFromIterationUpdateEvent(): void
    {
        $iteration_update_event      = IterationUpdateEventStub::withDefinedValues(
            self::ITERATION_ID,
            self::USER_ID,
            self::CHANGESET_ID
        );
        $iteration_tracker_retriever = RetrieveIterationTrackerStub::withValidTracker(self::ITERATION_TRACKER_ID);

        $iteration_update = IterationUpdate::fromIterationUpdateEvent(
            $iteration_tracker_retriever,
            $iteration_update_event
        );

        self::assertSame(self::ITERATION_ID, $iteration_update->getTimebox()->getId());
        self::assertSame(self::ITERATION_ID, $iteration_update->getIteration()->getId());
        self::assertSame(self::ITERATION_TRACKER_ID, $iteration_update->getTracker()->getId());
        self::assertSame(self::USER_ID, $iteration_update->getUser()->getId());
        self::assertSame(self::CHANGESET_ID, $iteration_update->getChangeset()->getId());
    }
}
