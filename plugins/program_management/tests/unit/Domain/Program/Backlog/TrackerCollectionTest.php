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

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\ProgramTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanSubmitStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

final class TrackerCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProgramIdentifier $program_identifier;
    private UserIdentifier $user_identifier;

    protected function setUp(): void
    {
        $this->program_identifier = ProgramIdentifierBuilder::build();
        $this->user_identifier    = UserIdentifierStub::buildGenericUser();
    }

    public function testGetEmptyTrackerArrayWhenNoRootPlanningTrackers(): void
    {
        $empty_teams = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(),
            new BuildProjectStub(),
            $this->program_identifier
        );
        $retriever   = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(78);
        $collection  = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $empty_teams, $this->user_identifier);
        self::assertEmpty($collection->getTrackerIds());
        self::assertEmpty($collection->getTrackers());
    }

    public function testGetEmptyTrackerArrayWhenNoSecondPlanningTrackers(): void
    {
        $empty_teams = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(),
            new BuildProjectStub(),
            $this->program_identifier
        );
        $retriever   = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(78);
        $collection  = TrackerCollection::buildSecondPlanningMilestoneTracker($retriever, $empty_teams, $this->user_identifier);
        self::assertEmpty($collection->getTrackerIds());
        self::assertEmpty($collection->getTrackers());
    }

    public function testReturnsTrueWhenUserCanSubmitInAllRootPlanningTrackers(): void
    {
        $teams         = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(103, 104),
            new BuildProjectStub(),
            $this->program_identifier
        );
        $first_tracker = $this->createMock(\Tracker::class);
        $first_tracker->method('userCanSubmitArtifact')->willReturn(true);
        $first_tracker->method('getId')->willReturn(78);
        $first_tracker->method('getName')->willReturn("tracker");
        $first_tracker->method('getGroupId')->willReturn(101);
        $second_tracker = $this->createMock(\Tracker::class);
        $second_tracker->method('userCanSubmitArtifact')->willReturn(true);
        $second_tracker->method('getId')->willReturn(57);
        $second_tracker->method('getName')->willReturn("tracker B");
        $second_tracker->method('getGroupId')->willReturn(101);
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackers(ProgramTrackerStub::fromTracker($first_tracker), ProgramTrackerStub::fromTracker($second_tracker));

        $collection = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $this->user_identifier);
        self::assertTrue($collection->canUserSubmitAnArtifactInAllTrackers($this->user_identifier, new ConfigurationErrorsCollector(false), VerifyUserCanSubmitStub::userCanSubmit()));

        $trackers = $collection->getTrackers();
        self::assertEquals($first_tracker->getId(), $trackers[0]->getId());
        self::assertEquals($second_tracker->getId(), $trackers[1]->getId());

        $ids = $collection->getTrackerIds();
        self::assertContains(78, $ids);
        self::assertContains(57, $ids);
    }

    public function testReturnsTrueWhenUserCanSubmitInAllSecondPlanningTrackers(): void
    {
        $teams         = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(103, 104),
            new BuildProjectStub(),
            $this->program_identifier
        );
        $first_tracker = $this->createMock(\Tracker::class);
        $first_tracker->method('userCanSubmitArtifact')->willReturn(true);
        $first_tracker->method('getId')->willReturn(78);
        $first_tracker->method('getName')->willReturn("tracker");
        $first_tracker->method('getGroupId')->willReturn(101);
        $second_tracker = $this->createMock(\Tracker::class);
        $second_tracker->method('userCanSubmitArtifact')->willReturn(true);
        $second_tracker->method('getId')->willReturn(57);
        $second_tracker->method('getName')->willReturn("tracker B");
        $second_tracker->method('getGroupId')->willReturn(101);
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackers(ProgramTrackerStub::fromTracker($first_tracker), ProgramTrackerStub::fromTracker($second_tracker));

        $collection = TrackerCollection::buildSecondPlanningMilestoneTracker($retriever, $teams, $this->user_identifier);
        self::assertTrue($collection->canUserSubmitAnArtifactInAllTrackers($this->user_identifier, new ConfigurationErrorsCollector(false), VerifyUserCanSubmitStub::userCanSubmit()));

        $trackers = $collection->getTrackers();
        self::assertEquals($first_tracker->getId(), $trackers[0]->getId());
        self::assertEquals($second_tracker->getId(), $trackers[1]->getId());
    }

    public function testReturnsFalseWhenUserCanNotSubmitAnArtifactInAllRootPlanningTrackers(): void
    {
        $teams     = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(103, 104),
            new BuildProjectStub(),
            $this->program_identifier
        );
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackers(ProgramTrackerStub::withId(1), ProgramTrackerStub::withId(2));

        $collection = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $this->user_identifier);
        self::assertFalse($collection->canUserSubmitAnArtifactInAllTrackers($this->user_identifier, new ConfigurationErrorsCollector(false), VerifyUserCanSubmitStub::userCanNotSubmit()));
    }

    public function testReturnsFalseWhenUserCanNotSubmitAnArtifactInAllSecondPlanningTrackers(): void
    {
        $teams     = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(103, 104),
            new BuildProjectStub(),
            $this->program_identifier
        );
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackers(ProgramTrackerStub::withId(1), ProgramTrackerStub::withId(2));

        $collection = TrackerCollection::buildSecondPlanningMilestoneTracker($retriever, $teams, $this->user_identifier);
        self::assertFalse($collection->canUserSubmitAnArtifactInAllTrackers($this->user_identifier, new ConfigurationErrorsCollector(false), VerifyUserCanSubmitStub::userCanNotSubmit()));
    }

    public function testCollectsAllInvalidTrackers(): void
    {
        $teams     = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(103, 104),
            new BuildProjectStub(),
            $this->program_identifier
        );
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackers(ProgramTrackerStub::withId(1), ProgramTrackerStub::withId(2));

        $collection           = TrackerCollection::buildSecondPlanningMilestoneTracker($retriever, $teams, $this->user_identifier);
        $configuration_errors = new ConfigurationErrorsCollector(true);
        self::assertFalse($collection->canUserSubmitAnArtifactInAllTrackers($this->user_identifier, $configuration_errors, VerifyUserCanSubmitStub::userCanNotSubmit()));
        self::assertCount(2, $configuration_errors->getTeamTrackerIdErrors());
    }

    public function testCollectsTheFirstError(): void
    {
        $teams     = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(103, 104),
            new BuildProjectStub(),
            $this->program_identifier
        );
        $retriever = RetrievePlanningMilestoneTrackerStub::withValidTrackers(ProgramTrackerStub::withId(1), ProgramTrackerStub::withId(2));

        $collection           = TrackerCollection::buildSecondPlanningMilestoneTracker($retriever, $teams, $this->user_identifier);
        $configuration_errors = new ConfigurationErrorsCollector(false);
        self::assertFalse($collection->canUserSubmitAnArtifactInAllTrackers($this->user_identifier, $configuration_errors, VerifyUserCanSubmitStub::userCanNotSubmit()));
        self::assertCount(1, $configuration_errors->getTeamTrackerIdErrors());
    }
}
