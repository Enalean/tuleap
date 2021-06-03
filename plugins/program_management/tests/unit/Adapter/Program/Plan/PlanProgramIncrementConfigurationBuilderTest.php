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

namespace Tuleap\ProgramManagement\Adapter\Program\Plan;

use Tuleap\ProgramManagement\Domain\Program\Plan\ConfigurationUserCanNotSeeProgramException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Stub\RetrieveProgramIncrementTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class PlanProgramIncrementConfigurationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\TrackerFactory
     */
    private $tracker_factory;

    protected function setUp(): void
    {
        $this->tracker_factory = $this->createMock(\TrackerFactory::class);
    }

    public function testItThrowsAnExceptionIfProgramIncrementTrackerIsNotFound(): void
    {
        $user = UserTestBuilder::aUser()->build();
        $this->tracker_factory->method('getTrackerById')->with(1)->willReturn(null);

        $builder = new PlanProgramIncrementConfigurationBuilder(
            RetrieveProgramIncrementTrackerStub::buildValidTrackerId(1),
            $this->tracker_factory
        );
        $this->expectException(ProgramNotFoundException::class);
        $builder->buildTrackerProgramIncrementFromProjectId(100, $user);
    }

    public function testItThrowsIfGivenProjectIsNotAProgram(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $builder = new PlanProgramIncrementConfigurationBuilder(
            RetrieveProgramIncrementTrackerStub::buildNoProgramIncrementTracker(),
            $this->tracker_factory
        );
        $this->expectException(ProgramTrackerNotFoundException::class);
        $builder->buildTrackerProgramIncrementFromProjectId(404, $user);
    }

    public function testItThrowsAnExceptionIfUserCanNotSeeProgramIncrementTracker(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getId')->willReturn(1);
        $tracker->method('userCanView')->willReturn(false);
        $this->tracker_factory->method('getTrackerById')->with(1)->willReturn($tracker);

        $builder = new PlanProgramIncrementConfigurationBuilder(
            RetrieveProgramIncrementTrackerStub::buildValidTrackerId(1),
            $this->tracker_factory
        );
        $this->expectException(ConfigurationUserCanNotSeeProgramException::class);
        $builder->buildTrackerProgramIncrementFromProjectId(100, $user);
    }

    public function testItBuildProgramIncrementTracker(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getId')->willReturn(1);
        $tracker->method('userCanView')->willReturn(true);
        $this->tracker_factory->method('getTrackerById')->with(1)->willReturn($tracker);

        $builder           = new PlanProgramIncrementConfigurationBuilder(
            RetrieveProgramIncrementTrackerStub::buildValidTrackerId(1),
            $this->tracker_factory
        );
        $program_increment = new ProgramTracker($tracker);
        self::assertEquals($program_increment, $builder->buildTrackerProgramIncrementFromProjectId(100, $user));
    }
}
