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
use Tuleap\ProgramManagement\Domain\Project;
use Tuleap\ProgramManagement\Stub\RetrieveRootPlanningMilestoneTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TrackerCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testGetTrackerIdsReturnsTrackerIds(): void
    {
        $first_team  = new Project(103, 'team_blue', 'Team Blue');
        $second_team = new Project(104, 'team_red', 'Team Red');
        $teams       = new TeamProjectsCollection([$first_team, $second_team]);
        $user        = UserTestBuilder::aUser()->build();
        $retriever   = RetrieveRootPlanningMilestoneTrackerStub::withValidTrackerIds(78, 57);

        $collection = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);
        $ids        = $collection->getTrackerIds();
        self::assertContains(78, $ids);
        self::assertContains(57, $ids);
    }

    public function testGetTrackerIdsReturnsEmpty(): void
    {
        $user        = UserTestBuilder::aUser()->build();
        $empty_teams = new TeamProjectsCollection([]);
        $retriever   = RetrieveRootPlanningMilestoneTrackerStub::withValidTrackerIds(78);
        $collection  = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $empty_teams, $user);
        self::assertEmpty($collection->getTrackerIds());
    }

    public function testGetTrackersReturnTrackers(): void
    {
        $first_team     = new Project(103, 'team_blue', 'Team Blue');
        $second_team    = new Project(104, 'team_red', 'Team Red');
        $teams          = new TeamProjectsCollection([$first_team, $second_team]);
        $user           = UserTestBuilder::aUser()->build();
        $first_tracker  = TrackerTestBuilder::aTracker()->withId(78)->build();
        $second_tracker = TrackerTestBuilder::aTracker()->withId(57)->build();
        $retriever      = RetrieveRootPlanningMilestoneTrackerStub::withValidTrackers($first_tracker, $second_tracker);

        $collection = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);
        $trackers   = $collection->getTrackers();
        self::assertEquals($first_tracker, $trackers[0]->getFullTracker());
        self::assertEquals($second_tracker, $trackers[1]->getFullTracker());
    }

    public function testGetTrackersReturnsEmpty(): void
    {
        $user        = UserTestBuilder::aUser()->build();
        $empty_teams = new TeamProjectsCollection([]);
        $retriever   = RetrieveRootPlanningMilestoneTrackerStub::withValidTrackerIds(78);
        $collection  = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $empty_teams, $user);
        self::assertEmpty($collection->getTrackers());
    }

    public function testCanUserSubmitAnArtifactInAllTrackersReturnsTrue(): void
    {
        $first_team    = new Project(103, 'team_blue', 'Team Blue');
        $second_team   = new Project(104, 'team_red', 'Team Red');
        $teams         = new TeamProjectsCollection([$first_team, $second_team]);
        $first_tracker = $this->createMock(\Tracker::class);
        $first_tracker->method('userCanSubmitArtifact')->willReturn(true);
        $second_tracker = $this->createMock(\Tracker::class);
        $second_tracker->method('userCanSubmitArtifact')->willReturn(true);
        $retriever = RetrieveRootPlanningMilestoneTrackerStub::withValidTrackers($first_tracker, $second_tracker);
        $user      = UserTestBuilder::aUser()->build();

        $collection = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);
        self::assertTrue($collection->canUserSubmitAnArtifactInAllTrackers($user));
    }

    public function testCanUserSubmitAnArtifactInAllTrackersReturnsFalse(): void
    {
        $first_team    = new Project(103, 'team_blue', 'Team Blue');
        $second_team   = new Project(104, 'team_red', 'Team Red');
        $teams         = new TeamProjectsCollection([$first_team, $second_team]);
        $first_tracker = $this->createMock(\Tracker::class);
        $first_tracker->method('userCanSubmitArtifact')->willReturn(true);
        $second_tracker = $this->createMock(\Tracker::class);
        $second_tracker->method('userCanSubmitArtifact')->willReturn(false);
        $retriever = RetrieveRootPlanningMilestoneTrackerStub::withValidTrackers($first_tracker, $second_tracker);
        $user      = UserTestBuilder::aUser()->build();

        $collection = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);
        self::assertFalse($collection->canUserSubmitAnArtifactInAllTrackers($user));
    }
}
