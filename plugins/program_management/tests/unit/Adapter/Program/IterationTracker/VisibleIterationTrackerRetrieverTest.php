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

use TrackerFactory;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

final class VisibleIterationTrackerRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ITERATION_TRACKER_ID = 75;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TrackerFactory
     */
    private $tracker_factory;
    private UserIdentifier $user_identifier;
    private RetrieveIterationTrackerStub $tracker_id_retriever;
    private ProgramIdentifier $program;
    private RetrieveUserStub $retrieve_user;

    protected function setUp(): void
    {
        $this->tracker_factory      = $this->createMock(TrackerFactory::class);
        $this->user_identifier      = UserIdentifierStub::buildGenericUser();
        $this->program              = ProgramIdentifierBuilder::build();
        $this->tracker_id_retriever = RetrieveIterationTrackerStub::withValidTracker(self::ITERATION_TRACKER_ID);
        $this->retrieve_user        = RetrieveUserStub::withGenericUser();
    }

    public function testGetNullIfNoTrackerIdFoundInConfiguration(): void
    {
        $this->tracker_id_retriever = RetrieveIterationTrackerStub::withNoIterationTracker();
        self::assertNull(
            $this->getRetriever()->retrieveVisibleIterationTracker($this->program, $this->user_identifier)
        );
    }

    private function getRetriever(): VisibleIterationTrackerRetriever
    {
        return new VisibleIterationTrackerRetriever($this->tracker_id_retriever, $this->tracker_factory, $this->retrieve_user);
    }

    public function testThrowExceptionIfTrackerIdIsNotATracker(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn(null);
        $this->expectException(ProgramTrackerNotFoundException::class);
        $this->getRetriever()->retrieveVisibleIterationTracker($this->program, $this->user_identifier);
    }

    public function testGetNullIfUserCanNotSeeTracker(): void
    {
        $tracker = $this->createStub(\Tracker::class);
        $tracker->method('getId')->willReturn(self::ITERATION_TRACKER_ID);
        $tracker->method('userCanView')->willReturn(false);
        $this->tracker_factory->method('getTrackerById')->with(self::ITERATION_TRACKER_ID)->willReturn($tracker);

        self::assertNull(
            $this->getRetriever()->retrieveVisibleIterationTracker($this->program, $this->user_identifier)
        );
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

        self::assertSame(
            self::ITERATION_TRACKER_ID,
            $this->getRetriever()->retrieveVisibleIterationTracker($this->program, $this->user_identifier)?->getId()
        );
    }
}
