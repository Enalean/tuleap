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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Project;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\ProgramManagement\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class SourceTrackerCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsValidCollection(): void
    {
        $user      = UserTestBuilder::aUser()->build();
        $program   = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 101, $user);
        $team_blue = new Project(102, 'team_blue', 'Team Blue');
        $team_red  = new Project(103, 'tea_red', 'Team Red');
        $teams     = new TeamProjectsCollection([$team_blue, $team_red]);

        $program_increment_tracker = TrackerTestBuilder::aTracker()->withId(78)->build();
        $blue_team_tracker         = TrackerTestBuilder::aTracker()->withId(79)->build();
        $red_team_tracker          = TrackerTestBuilder::aTracker()->withId(80)->build();

        $team_trackers = TrackerCollection::buildRootPlanningMilestoneTrackers(
            RetrievePlanningMilestoneTrackerStub::withValidTrackers($blue_team_tracker, $red_team_tracker),
            $teams,
            $user
        );

        $collection = SourceTrackerCollection::fromProgramAndTeamTrackers(
            RetrieveVisibleProgramIncrementTrackerStub::withValidTracker($program_increment_tracker),
            $program,
            $team_trackers,
            $user
        );
        $trackers   = $collection->getSourceTrackers();
        self::assertContainsEquals(new ProgramTracker($program_increment_tracker), $trackers);
        self::assertContainsEquals(new ProgramTracker($blue_team_tracker), $trackers);
        self::assertContainsEquals(new ProgramTracker($red_team_tracker), $trackers);
    }
}
