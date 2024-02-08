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

namespace Tuleap\ProgramManagement\Adapter\Program\ProgramIncrementTracker;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramHasNoProgramIncrementTrackerException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

final class VisibleProgramIncrementTrackerRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\TrackerFactory
     */
    private $tracker_factory;
    private UserIdentifier $user;
    private ProgramIdentifier $program;
    private RetrieveProgramIncrementTracker $tracker_id_retriever;

    protected function setUp(): void
    {
        $this->tracker_factory      = $this->createMock(\TrackerFactory::class);
        $this->user                 = UserIdentifierStub::buildGenericUser();
        $this->program              = ProgramIdentifierBuilder::build();
        $this->tracker_id_retriever = RetrieveProgramIncrementTrackerStub::withValidTracker(1);
    }

    public function testItThrowsAnExceptionIfProgramIncrementTrackerIsNotFound(): void
    {
        $this->tracker_factory->method('getTrackerById')->with(1)->willReturn(null);

        $this->expectException(ProgramTrackerNotFoundException::class);
        $this->getRetriever()->retrieveVisibleProgramIncrementTracker($this->program, $this->user);
    }

    public function testItThrowsIfGivenProjectIsNotAProgram(): void
    {
        $this->tracker_id_retriever = RetrieveProgramIncrementTrackerStub::withNoProgramIncrementTracker();

        $this->expectException(ProgramHasNoProgramIncrementTrackerException::class);
        $this->getRetriever()->retrieveVisibleProgramIncrementTracker($this->program, $this->user);
    }

    public function testItThrowsAnExceptionIfUserCanNotSeeProgramIncrementTracker(): void
    {
        $tracker = $this->createStub(\Tracker::class);
        $tracker->method('getId')->willReturn(1);
        $tracker->method('userCanView')->willReturn(false);
        $this->tracker_factory->method('getTrackerById')->with(1)->willReturn($tracker);

        $this->expectException(ProgramTrackerNotFoundException::class);
        $this->getRetriever()->retrieveVisibleProgramIncrementTracker($this->program, $this->user);
    }

    public function testItBuildProgramIncrementTracker(): void
    {
        $tracker = $this->createStub(\Tracker::class);
        $tracker->method('getId')->willReturn(1);
        $tracker->method('getName')->willReturn('Tracker');
        $tracker->method('getGroupId')->willReturn(101);
        $tracker->method('userCanView')->willReturn(true);
        $tracker->method('getProject')->willReturn(new \Project(['group_id' => 101, 'group_name' => 'A project']));
        $this->tracker_factory->method('getTrackerById')->with(1)->willReturn($tracker);

        self::assertEquals(
            $tracker->getId(),
            $this->getRetriever()->retrieveVisibleProgramIncrementTracker($this->program, $this->user)->getId()
        );
    }

    private function getRetriever(): VisibleProgramIncrementTrackerRetriever
    {
        return new VisibleProgramIncrementTrackerRetriever($this->tracker_id_retriever, $this->tracker_factory, RetrieveUserStub::withGenericUser());
    }
}
