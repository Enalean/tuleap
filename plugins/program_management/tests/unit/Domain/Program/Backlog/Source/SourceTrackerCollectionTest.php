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

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SourceTrackerCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TIMEBOX_TRACKER_ID   = 58;
    private const BLUE_TEAM_TRACKER_ID = 79;
    private const RED_TEAM_TRACKER_ID  = 80;

    private TeamProjectsCollection $teams;
    private UserIdentifier $user;
    private ProgramIdentifier $program;
    private TrackerCollection $team_trackers;
    private TrackerReference $timebox_tracker;

    protected function setUp(): void
    {
        $this->user    = UserIdentifierStub::buildGenericUser();
        $this->program = ProgramIdentifierBuilder::build();

        $this->teams = TeamProjectsCollectionBuilder::withProjects(
            ProjectReferenceStub::withId(102),
            ProjectReferenceStub::withId(103),
        );

        $this->timebox_tracker = TrackerReferenceStub::withId(self::TIMEBOX_TRACKER_ID);

        $this->team_trackers = TrackerCollection::buildRootPlanningMilestoneTrackers(
            RetrieveMirroredProgramIncrementTrackerStub::withValidTrackers(
                TrackerReferenceStub::withId(self::BLUE_TEAM_TRACKER_ID),
                TrackerReferenceStub::withId(self::RED_TEAM_TRACKER_ID),
            ),
            $this->teams,
            $this->user,
            new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false)
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
        $ids        = $collection->getSourceTrackerIds();
        self::assertContains(self::TIMEBOX_TRACKER_ID, $ids);
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

        $trackers = $collection?->getSourceTrackers();
        self::assertNotNull($trackers);
        self::assertContainsEquals($this->timebox_tracker, $trackers);
        $ids = $collection?->getSourceTrackerIds();
        self::assertNotNull($ids);
        self::assertContains(self::TIMEBOX_TRACKER_ID, $ids);
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
