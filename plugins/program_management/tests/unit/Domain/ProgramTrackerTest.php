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

use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramTrackerTest extends TestCase
{
    private UserIdentifierStub $user_identifier;

    protected function setUp(): void
    {
        $this->user_identifier = UserIdentifierStub::buildGenericUser();
    }

    public function testItBuildsMilestoneTrackerFromRootPlanning(): void
    {
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(101);
        $project   = new ProgramManagementProject(101, 'team_blue', 'Team Blue', '/team_blue');

        $tracker = ProgramTracker::buildMilestoneTrackerFromRootPlanning($retriever, $project, $this->user_identifier);
        self::assertSame(101, $tracker->getTrackerId());
    }

    public function testItBuildsMilestoneTrackerFromSecondPlanning(): void
    {
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(76);
        $project   = new ProgramManagementProject(101, 'team_blue', 'Team Blue', '/team_blue');

        $tracker = ProgramTracker::buildSecondPlanningMilestoneTracker($retriever, $project, $this->user_identifier);
        self::assertSame(76, $tracker->getTrackerId());
    }

    public function testItBuildsProgramIncrementTracker(): void
    {
        $tracker   = TrackerTestBuilder::aTracker()->withId(78)->build();
        $retriever = RetrieveVisibleProgramIncrementTrackerStub::withValidTracker($tracker);
        $program   = ProgramIdentifierBuilder::build();

        $program_increment_tracker = ProgramTracker::buildProgramIncrementTrackerFromProgram($retriever, $program, $this->user_identifier);
        self::assertSame(78, $program_increment_tracker->getTrackerId());
    }

    public function testItBuildsIterationTracker(): void
    {
        $tracker   = TrackerTestBuilder::aTracker()->withId(78)->build();
        $retriever = RetrieveVisibleIterationTrackerStub::withValidTracker($tracker);
        $program   = ProgramIdentifierBuilder::build();

        $iteration_tracker = ProgramTracker::buildIterationTrackerFromProgram($retriever, $program, $this->user_identifier);
        self::assertSame(78, $iteration_tracker->getTrackerId());
    }

    public function testItReturnsNullIfNoIterationTracker(): void
    {
        $retriever = RetrieveVisibleIterationTrackerStub::withNotVisibleIterationTracker();
        $program   = ProgramIdentifierBuilder::build();

        $iteration_tracker = ProgramTracker::buildIterationTrackerFromProgram($retriever, $program, $this->user_identifier);
        self::assertNull($iteration_tracker);
    }

    public function testItDelegatesPermissionCheckToWrappedTracker(): void
    {
        $retrieve_user = RetrieveUserStub::withGenericUser();
        $project       = new ProgramManagementProject(101, 'team_blue', 'Team Blue', '/team_blue');
        $base_tracker  = $this->createMock(\Tracker::class);
        $base_tracker->method('userCanSubmitArtifact')->willReturn(true);
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackers($base_tracker);

        $tracker = ProgramTracker::buildMilestoneTrackerFromRootPlanning($retriever, $project, $this->user_identifier);
        self::assertTrue($tracker->userCanSubmitArtifact($retrieve_user, $this->user_identifier));
        self::assertSame($base_tracker, $tracker->getFullTracker());
    }
}
