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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Source;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\ProgramTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class SourceTrackerCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const BLUE_TEAM_TRACKER_ID = 79;
    private const RED_TEAM_TRACKER_ID  = 80;

    private TeamProjectsCollection $teams;
    private UserIdentifier $user;
    private ProgramIdentifier $program;
    private \Tracker $blue_team_tracker;
    private \Tracker $red_team_tracker;
    private TrackerCollection $team_trackers;
    private ProgramTracker $timebox_tracker;

    protected function setUp(): void
    {
        $this->user    = UserIdentifierStub::buildGenericUser();
        $this->program = ProgramIdentifierBuilder::build();

        $this->teams = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(102, 103),
            new BuildProjectStub(),
            $this->program
        );

        $this->timebox_tracker   = ProgramTrackerStub::withDefaults();
        $this->blue_team_tracker = TrackerTestBuilder::aTracker()->withId(self::BLUE_TEAM_TRACKER_ID)->build();
        $this->red_team_tracker  = TrackerTestBuilder::aTracker()->withId(self::RED_TEAM_TRACKER_ID)->build();

        $this->team_trackers = TrackerCollection::buildRootPlanningMilestoneTrackers(
            RetrievePlanningMilestoneTrackerStub::withValidTrackers(
                ProgramTrackerStub::fromTracker($this->blue_team_tracker),
                ProgramTrackerStub::fromTracker($this->red_team_tracker)
            ),
            $this->teams,
            $this->user
        );
    }

    public function testItBuildsValidCollectionFromProgramIncrement(): void
    {
        $collection = SourceTrackerCollection::fromProgramAndTeamTrackers(
            RetrieveVisibleProgramIncrementTrackerStub::withValidTracker($this->timebox_tracker),
            $this->program,
            $this->team_trackers,
            $this->user
        );
        $trackers   = $collection->getSourceTrackers();
        $ids        = $collection->getSourceTrackerIds();
        self::assertContainsEquals($this->timebox_tracker, $trackers);
        self::assertContains(self::BLUE_TEAM_TRACKER_ID, $ids);
        self::assertContains(self::RED_TEAM_TRACKER_ID, $ids);
    }

    public function testItBuildsValidCollectionFromIteration(): void
    {
        $collection = SourceTrackerCollection::fromIterationAndTeamTrackers(
            RetrieveVisibleIterationTrackerStub::withValidTracker($this->timebox_tracker),
            $this->program,
            $this->team_trackers,
            $this->user
        );

        $trackers = $collection->getSourceTrackers();
        self::assertContainsEquals($this->timebox_tracker, $trackers);
        $ids = $collection->getSourceTrackerIds();
        self::assertContains(self::BLUE_TEAM_TRACKER_ID, $ids);
        self::assertContains(self::RED_TEAM_TRACKER_ID, $ids);
    }

    public function testItBuildsNullCollectionFromIteration(): void
    {
        $collection = SourceTrackerCollection::fromIterationAndTeamTrackers(
            RetrieveVisibleIterationTrackerStub::withNotVisibleIterationTracker(),
            $this->program,
            $this->team_trackers,
            $this->user
        );

        self::assertNull($collection);
    }
}
