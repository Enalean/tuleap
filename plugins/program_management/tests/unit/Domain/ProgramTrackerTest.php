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

namespace Tuleap\ProgramManagement\Domain;

use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\ProgramManagement\Stub\RetrieveVisibleIterationTrackerStub;
use Tuleap\ProgramManagement\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramTrackerTest extends TestCase
{
    public function testItBuildsMilestoneTrackerFromRootPlanning(): void
    {
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(101);
        $project   = new Project(101, 'team_blue', 'Team Blue');
        $user      = UserTestBuilder::aUser()->build();

        $tracker = ProgramTracker::buildMilestoneTrackerFromRootPlanning($retriever, $project, $user);
        self::assertSame(101, $tracker->getTrackerId());
    }

    public function testItBuildsMilestoneTrackerFromSecondPlanning(): void
    {
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(76);
        $project   = new Project(101, 'team_blue', 'Team Blue');
        $user      = UserTestBuilder::aUser()->build();

        $tracker = ProgramTracker::buildSecondPlanningMilestoneTracker($retriever, $project, $user);
        self::assertSame(76, $tracker->getTrackerId());
    }

    public function testItBuildsProgramIncrementTracker(): void
    {
        $tracker   = TrackerTestBuilder::aTracker()->withId(78)->build();
        $retriever = RetrieveVisibleProgramIncrementTrackerStub::withValidTracker($tracker);
        $user      = UserTestBuilder::aUser()->build();
        $program   = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 101, $user);

        $program_increment_tracker = ProgramTracker::buildProgramIncrementTrackerFromProgram($retriever, $program, $user);
        self::assertSame(78, $program_increment_tracker->getTrackerId());
    }

    public function testItBuildsIterationTracker(): void
    {
        $tracker   = TrackerTestBuilder::aTracker()->withId(78)->build();
        $retriever = RetrieveVisibleIterationTrackerStub::withValidTracker($tracker);
        $user      = UserTestBuilder::aUser()->build();
        $program   = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 101, $user);

        $iteration_tracker = ProgramTracker::buildIterationTrackerFromProgram($retriever, $program, $user);
        self::assertSame(78, $iteration_tracker->getTrackerId());
    }

    public function testItReturnsNullIfNoIterationTracker(): void
    {
        $retriever = RetrieveVisibleIterationTrackerStub::withNotVisibleIterationTracker();
        $user      = UserTestBuilder::aUser()->build();
        $program   = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 101, $user);

        $iteration_tracker = ProgramTracker::buildIterationTrackerFromProgram($retriever, $program, $user);
        self::assertNull($iteration_tracker);
    }

    public function testItDelegatesPermissionCheckToWrappedTracker(): void
    {
        $user         = UserTestBuilder::aUser()->build();
        $project      = new Project(101, 'team_blue', 'Team Blue');
        $base_tracker = $this->createMock(\Tracker::class);
        $base_tracker->method('userCanSubmitArtifact')->with($user)->willReturn(true);
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackers($base_tracker);

        $tracker = ProgramTracker::buildMilestoneTrackerFromRootPlanning($retriever, $project, $user);
        self::assertTrue($tracker->userCanSubmitArtifact($user));
        self::assertSame($base_tracker, $tracker->getFullTracker());
    }
}
