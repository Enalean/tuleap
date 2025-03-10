<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\IterationTracker;

use PHPUnit\Framework\MockObject\MockObject;
use TrackerFactory;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class VisibleIterationTrackerRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ITERATION_TRACKER_ID = 75;
    private TrackerFactory&MockObject $tracker_factory;
    private RetrieveIterationTrackerStub $tracker_id_retriever;

    protected function setUp(): void
    {
        $this->tracker_factory      = $this->createMock(TrackerFactory::class);
        $this->tracker_id_retriever = RetrieveIterationTrackerStub::withValidTracker(self::ITERATION_TRACKER_ID);
    }

    private function retrieve(): ?TrackerReference
    {
        $retriever = new VisibleIterationTrackerRetriever(
            $this->tracker_id_retriever,
            $this->tracker_factory,
            RetrieveUserStub::withUser(UserTestBuilder::buildWithId(810))
        );
        return $retriever->retrieveVisibleIterationTracker(
            ProgramIdentifierBuilder::build(),
            UserIdentifierStub::withId(810)
        );
    }

    public function testGetNullIfNoTrackerIdFoundInConfiguration(): void
    {
        $this->tracker_id_retriever = RetrieveIterationTrackerStub::withNoIterationTracker();
        self::assertNull($this->retrieve());
    }

    public function testThrowExceptionIfTrackerIdIsNotATracker(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn(null);
        $this->expectException(ProgramTrackerNotFoundException::class);
        $this->retrieve();
    }

    public function testGetNullIfUserCanNotSeeTracker(): void
    {
        $tracker = $this->createStub(\Tracker::class);
        $tracker->method('getId')->willReturn(self::ITERATION_TRACKER_ID);
        $tracker->method('userCanView')->willReturn(false);
        $this->tracker_factory->method('getTrackerById')->with(self::ITERATION_TRACKER_ID)->willReturn($tracker);

        self::assertNull($this->retrieve());
    }

    public function testGetTrackerWhenItIsExistAndUserSeeIt(): void
    {
        $tracker = $this->createStub(\Tracker::class);
        $tracker->method('getId')->willReturn(self::ITERATION_TRACKER_ID);
        $tracker->method('getName')->willReturn('Tracker 1');
        $tracker->method('getGroupId')->willReturn(101);
        $tracker->method('userCanView')->willReturn(true);
        $tracker->method('getProject')->willReturn(new \Project(['group_id' => 101, 'group_name' => 'A project']));
        $this->tracker_factory->method('getTrackerById')->with(self::ITERATION_TRACKER_ID)->willReturn($tracker);

        self::assertSame(self::ITERATION_TRACKER_ID, $this->retrieve()?->getId());
    }
}
