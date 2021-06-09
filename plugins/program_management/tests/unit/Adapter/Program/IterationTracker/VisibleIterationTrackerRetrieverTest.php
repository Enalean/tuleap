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
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\RetrieveIterationTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class VisibleIterationTrackerRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|TrackerFactory
     */
    private $tracker_factory;
    private \PFUser $user;
    private RetrieveIterationTrackerStub $tracker_id_retriever;
    private ProgramIdentifier $program;

    protected function setUp(): void
    {
        $this->tracker_factory      = $this->createStub(TrackerFactory::class);
        $this->user                 = UserTestBuilder::aUser()->build();
        $this->program              = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 101, $this->user);
        $this->tracker_id_retriever = RetrieveIterationTrackerStub::buildValidTrackerId(1);
    }

    public function testGetNullIfNoTrackerIdFoundInConfiguration(): void
    {
        $this->tracker_id_retriever = RetrieveIterationTrackerStub::buildNoIterationTracker();
        self::assertNull(
            $this->getRetriever()->retrieveVisibleIterationTracker($this->program, $this->user)
        );
    }

    private function getRetriever(): VisibleIterationTrackerRetriever
    {
        return new VisibleIterationTrackerRetriever($this->tracker_id_retriever, $this->tracker_factory);
    }

    public function testThrowExceptionIfTrackerIdIsNotATracker(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn(null);
        $this->expectException(ProgramTrackerNotFoundException::class);
        $this->getRetriever()->retrieveVisibleIterationTracker($this->program, $this->user);
    }

    public function testGetNullIfUserCanNotSeeTracker(): void
    {
        $tracker = $this->createStub(\Tracker::class);
        $tracker->method('getId')->willReturn(1);
        $tracker->method('userCanView')->willReturn(false);
        $this->tracker_factory->method('getTrackerById')->with(1)->willReturn($tracker);

        self::assertNull(
            $this->getRetriever()->retrieveVisibleIterationTracker($this->program, $this->user)
        );
    }

    public function testGetTrackerWhenItIsExistAndUserSeeIt(): void
    {
        $tracker = $this->createStub(\Tracker::class);
        $tracker->method('getId')->willReturn(1);
        $tracker->method('userCanView')->willReturn(true);
        $this->tracker_factory->method('getTrackerById')->with(1)->willReturn($tracker);

        self::assertSame(
            $tracker,
            $this->getRetriever()->retrieveVisibleIterationTracker($this->program, $this->user)
        );
    }
}
