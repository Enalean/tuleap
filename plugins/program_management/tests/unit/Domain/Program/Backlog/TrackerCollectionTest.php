<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Stub\ProgramStoreStub;
use Tuleap\ProgramManagement\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class TrackerCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testGetEmptyTrackerArrayEmptyWhenNoRootPlanningTrackers(): void
    {
        $user        = UserTestBuilder::aUser()->build();
        $empty_teams = TeamProjectsCollection::fromProgramIdentifier(
            ProgramStoreStub::buildTeams(),
            new BuildProjectStub(),
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
        );
        $retriever   = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(78);
        $collection  = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $empty_teams, $user);
        self::assertEmpty($collection->getTrackerIds());
        self::assertEmpty($collection->getTrackers());
    }

    public function testGetEmptyTrackerArrayEmptyWhenNoSecondPlanningTrackers(): void
    {
        $user        = UserTestBuilder::aUser()->build();
        $empty_teams = TeamProjectsCollection::fromProgramIdentifier(
            ProgramStoreStub::buildTeams(),
            new BuildProjectStub(),
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
        );
        $retriever   = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(78);
        $collection  = TrackerCollection::buildSecondPlanningMilestoneTracker($retriever, $empty_teams, $user);
        self::assertEmpty($collection->getTrackerIds());
        self::assertEmpty($collection->getTrackers());
    }

    public function testReturnsTrueWhenUserCanSubmitInAllRootPlanningTrackers(): void
    {
        $teams         = TeamProjectsCollection::fromProgramIdentifier(
            ProgramStoreStub::buildTeams(103, 104),
            new BuildProjectStub(),
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
        );
        $first_tracker = $this->createMock(\Tracker::class);
        $first_tracker->method('userCanSubmitArtifact')->willReturn(true);
        $first_tracker->method('getId')->willReturn(78);
        $second_tracker = $this->createMock(\Tracker::class);
        $second_tracker->method('userCanSubmitArtifact')->willReturn(true);
        $second_tracker->method('getId')->willReturn(57);
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackers($first_tracker, $second_tracker);
        $user      = UserTestBuilder::aUser()->build();

        $collection = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);
        self::assertTrue($collection->canUserSubmitAnArtifactInAllTrackers($user));

        $trackers = $collection->getTrackers();
        self::assertEquals($first_tracker, $trackers[0]->getFullTracker());
        self::assertEquals($second_tracker, $trackers[1]->getFullTracker());

        $ids = $collection->getTrackerIds();
        self::assertContains(78, $ids);
        self::assertContains(57, $ids);
    }

    public function testReturnsTrueWhenUserCanSubmitInAllSecondPlanningTrackers(): void
    {
        $teams         = TeamProjectsCollection::fromProgramIdentifier(
            ProgramStoreStub::buildTeams(103, 104),
            new BuildProjectStub(),
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
        );
        $first_tracker = $this->createMock(\Tracker::class);
        $first_tracker->method('userCanSubmitArtifact')->willReturn(true);
        $first_tracker->method('getId')->willReturn(78);
        $second_tracker = $this->createMock(\Tracker::class);
        $second_tracker->method('userCanSubmitArtifact')->willReturn(true);
        $second_tracker->method('getId')->willReturn(57);
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackers($first_tracker, $second_tracker);
        $user      = UserTestBuilder::aUser()->build();

        $collection = TrackerCollection::buildSecondPlanningMilestoneTracker($retriever, $teams, $user);
        self::assertTrue($collection->canUserSubmitAnArtifactInAllTrackers($user));

        $trackers = $collection->getTrackers();
        self::assertEquals($first_tracker, $trackers[0]->getFullTracker());
        self::assertEquals($second_tracker, $trackers[1]->getFullTracker());
    }

    public function testReturnsFalseWhenUserCanNotSubmitAnArtifactInAllRootPlanningTrackers(): void
    {
        $teams         = TeamProjectsCollection::fromProgramIdentifier(
            ProgramStoreStub::buildTeams(103, 104),
            new BuildProjectStub(),
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
        );
        $first_tracker = $this->createMock(\Tracker::class);
        $first_tracker->method('userCanSubmitArtifact')->willReturn(true);
        $second_tracker = $this->createMock(\Tracker::class);
        $second_tracker->method('userCanSubmitArtifact')->willReturn(false);
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackers($first_tracker, $second_tracker);
        $user      = UserTestBuilder::aUser()->build();

        $collection = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);
        self::assertFalse($collection->canUserSubmitAnArtifactInAllTrackers($user));
    }

    public function testReturnsFalseWhenUserCanNotSubmitAnArtifactInAllSecondPlanningTrackers(): void
    {
        $teams         = TeamProjectsCollection::fromProgramIdentifier(
            ProgramStoreStub::buildTeams(103, 104),
            new BuildProjectStub(),
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
        );
        $first_tracker = $this->createMock(\Tracker::class);
        $first_tracker->method('userCanSubmitArtifact')->willReturn(true);
        $second_tracker = $this->createMock(\Tracker::class);
        $second_tracker->method('userCanSubmitArtifact')->willReturn(false);
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackers($first_tracker, $second_tracker);
        $user      = UserTestBuilder::aUser()->build();

        $collection = TrackerCollection::buildSecondPlanningMilestoneTracker($retriever, $teams, $user);
        self::assertFalse($collection->canUserSubmitAnArtifactInAllTrackers($user));
    }
}
