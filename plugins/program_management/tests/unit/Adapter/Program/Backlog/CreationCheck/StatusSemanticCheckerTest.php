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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\CreationCheck;

use Tracker;
use Tracker_FormElement_Field_List;
use Tracker_Semantic_Status;
use Tracker_Semantic_StatusDao;
use Tracker_Semantic_StatusFactory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Stub\ProgramStoreStub;
use Tuleap\ProgramManagement\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\ProgramManagement\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class StatusSemanticCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private StatusSemanticChecker $checker;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Tracker_Semantic_StatusDao
     */
    private $semantic_status_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Tracker_Semantic_StatusFactory
     */
    private $semantic_status_factory;

    private TrackerCollection $collection;
    private Tracker $tracker_team_01;
    private Tracker $tracker_team_02;
    private SourceTrackerCollection $source_trackers;
    private Tracker $timebox_tracker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Tracker_Semantic_Status
     */
    private $timebox_tracker_semantic_status;
    private Tracker $program_increment;
    private ProgramTracker $program_increment_tracker;

    protected function setUp(): void
    {
        $this->semantic_status_dao     = $this->createMock(Tracker_Semantic_StatusDao::class);
        $this->semantic_status_factory = $this->createMock(Tracker_Semantic_StatusFactory::class);

        $this->checker = new StatusSemanticChecker(
            $this->semantic_status_dao,
            $this->semantic_status_factory
        );

        $this->tracker_team_01 = TrackerTestBuilder::aTracker()->withId(123)->build();
        $this->tracker_team_02 = TrackerTestBuilder::aTracker()->withId(124)->build();

        $this->timebox_tracker                 = TrackerTestBuilder::aTracker()->withId(1)->build();
        $this->timebox_tracker_semantic_status = $this->createMock(Tracker_Semantic_Status::class);
        $this->timebox_tracker_semantic_status->method('getOpenLabels')->willReturn(['open', 'review']);

        $this->program_increment         = TrackerTestBuilder::aTracker()->withId(104)->build();
        $this->program_increment_tracker = new ProgramTracker($this->program_increment);

        $teams                 = TeamProjectsCollection::fromProgramIdentifier(
            ProgramStoreStub::buildTeams(101, 102),
            new BuildProjectStub(),
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 100, UserTestBuilder::aUser()->build())
        );
        $retriever             = RetrievePlanningMilestoneTrackerStub::withValidTrackers(
            $this->tracker_team_01,
            $this->tracker_team_02
        );
        $user                  = UserTestBuilder::aUser()->build();
        $this->collection      = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);
        $this->source_trackers = SourceTrackerCollection::fromProgramAndTeamTrackers(
            RetrieveVisibleProgramIncrementTrackerStub::withValidTracker($this->timebox_tracker),
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 101, $user),
            $this->collection,
            $user
        );
    }

    public function testItReturnsTrueIfAllStatusSemanticAreWellConfigured(): void
    {
        $list_field = $this->createMock(Tracker_FormElement_Field_List::class);

        $top_planning_tracker_semantic_status = $this->createMock(Tracker_Semantic_Status::class);
        $top_planning_tracker_semantic_status->method('getField')
            ->willReturn($list_field);

        $this->semantic_status_dao->method('getNbOfTrackerWithoutSemanticStatusDefined')
            ->with([1, 123, 124])
            ->willReturn(0);

        $top_planning_tracker_semantic_status->expects(self::once())
            ->method('getOpenLabels')
            ->willReturn(['open', 'review']);

        $tracker_01_semantic_status = $this->createMock(Tracker_Semantic_Status::class);
        $tracker_01_semantic_status->expects(self::once())
            ->method('getOpenLabels')
            ->willReturn(['open', 'review']);

        $tracker_02_semantic_status = $this->createMock(Tracker_Semantic_Status::class);
        $tracker_02_semantic_status->expects(self::once())
            ->method('getOpenLabels')
            ->willReturn(['open', 'in progress', 'review']);

        $this->semantic_status_factory->method('getByTracker')
            ->withConsecutive([$this->program_increment], [$this->timebox_tracker], [$this->tracker_team_01], [$this->tracker_team_02])
            ->willReturnOnConsecutiveCalls(
                $top_planning_tracker_semantic_status,
                $this->timebox_tracker_semantic_status,
                $tracker_01_semantic_status,
                $tracker_02_semantic_status
            );

        self::assertTrue(
            $this->checker->isStatusWellConfigured(
                $this->program_increment_tracker,
                $this->source_trackers
            )
        );
    }

    public function testItReturnsFalseIfProgramTrackerDoesNotHaveStatusSemantic(): void
    {
        $top_planning_tracker_semantic_status = $this->createMock(Tracker_Semantic_Status::class);
        $this->semantic_status_factory->method('getByTracker')
            ->with($this->program_increment)
            ->willReturn($top_planning_tracker_semantic_status);

        $top_planning_tracker_semantic_status->method('getField')
            ->willReturn(null);

        self::assertFalse(
            $this->checker->isStatusWellConfigured(
                $this->program_increment_tracker,
                $this->source_trackers
            )
        );
    }

    public function testItReturnsFalseIfSomeTeamTrackersDoNotHaveSemanticStatusDefined(): void
    {
        $top_planning_tracker_semantic_status = $this->createMock(Tracker_Semantic_Status::class);
        $this->semantic_status_factory->method('getByTracker')
            ->with($this->program_increment)
            ->willReturn($top_planning_tracker_semantic_status);

        $list_field = $this->createMock(Tracker_FormElement_Field_List::class);
        $top_planning_tracker_semantic_status->method('getField')
            ->willReturn($list_field);

        $this->semantic_status_dao->method('getNbOfTrackerWithoutSemanticStatusDefined')
            ->with([1, 123, 124])
            ->willReturn(1);

        self::assertFalse(
            $this->checker->isStatusWellConfigured(
                $this->program_increment_tracker,
                $this->source_trackers
            )
        );
    }

    public function testItReturnsFalseIfSomeTeamStatusSemanticDoesNotContainTheProgramOpenValue(): void
    {
        $list_field = $this->createMock(Tracker_FormElement_Field_List::class);

        $top_planning_tracker_semantic_status = $this->createMock(Tracker_Semantic_Status::class);
        $top_planning_tracker_semantic_status->method('getField')
            ->willReturn($list_field);

        $this->semantic_status_dao->method('getNbOfTrackerWithoutSemanticStatusDefined')
            ->with([1, 123, 124])
            ->willReturn(0);

        $top_planning_tracker_semantic_status->expects(self::once())
            ->method('getOpenLabels')
            ->willReturn(['open', 'review']);

        $tracker_01_semantic_status = $this->createMock(Tracker_Semantic_Status::class);
        $this->semantic_status_factory->method('getByTracker')
            ->withConsecutive([$this->program_increment], [$this->timebox_tracker], [$this->tracker_team_01])
            ->willReturnOnConsecutiveCalls($top_planning_tracker_semantic_status, $this->timebox_tracker_semantic_status, $tracker_01_semantic_status);

        $tracker_01_semantic_status->expects(self::once())
            ->method('getOpenLabels')
            ->willReturn(['open']);

        self::assertFalse(
            $this->checker->isStatusWellConfigured(
                $this->program_increment_tracker,
                $this->source_trackers
            )
        );
    }
}
