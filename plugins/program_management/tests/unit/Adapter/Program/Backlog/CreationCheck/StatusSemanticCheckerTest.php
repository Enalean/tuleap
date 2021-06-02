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
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Project;
use Tuleap\ProgramManagement\Stub\RetrieveRootPlanningMilestoneTrackerStub;
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

        $first_team       = new Project(101, 'team_blue', 'Team Blue');
        $second_team      = new Project(102, 'team_red', 'Team Red');
        $teams            = new TeamProjectsCollection([$first_team, $second_team]);
        $retriever        = RetrieveRootPlanningMilestoneTrackerStub::withValidTrackers(
            $this->tracker_team_01,
            $this->tracker_team_02
        );
        $user             = UserTestBuilder::aUser()->build();
        $this->collection = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);
    }

    public function testItReturnsTrueIfAllStatusSemanticAreWellConfigured(): void
    {
        $tracker                   = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_increment_tracker = new ProgramTracker($tracker);

        $list_field = $this->createMock(Tracker_FormElement_Field_List::class);

        $top_planning_tracker_semantic_status = $this->createMock(Tracker_Semantic_Status::class);
        $top_planning_tracker_semantic_status->method('getField')
            ->willReturn($list_field);

        $this->semantic_status_dao->method('getNbOfTrackerWithoutSemanticStatusDefined')
            ->with([123, 124])
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
            ->withConsecutive([$tracker], [$this->tracker_team_01], [$this->tracker_team_02])
            ->willReturnOnConsecutiveCalls(
                $top_planning_tracker_semantic_status,
                $tracker_01_semantic_status,
                $tracker_02_semantic_status
            );

        self::assertTrue(
            $this->checker->isStatusWellConfigured(
                $program_increment_tracker,
                $this->collection
            )
        );
    }

    public function testItReturnsFalseIfProgramTrackerDoesNotHaveStatusSemantic(): void
    {
        $tracker                   = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_increment_tracker = new ProgramTracker($tracker);

        $top_planning_tracker_semantic_status = $this->createMock(Tracker_Semantic_Status::class);
        $this->semantic_status_factory->method('getByTracker')
            ->with($tracker)
            ->willReturn($top_planning_tracker_semantic_status);

        $top_planning_tracker_semantic_status->method('getField')
            ->willReturn(null);

        self::assertFalse(
            $this->checker->isStatusWellConfigured(
                $program_increment_tracker,
                $this->collection
            )
        );
    }

    public function testItReturnsFalseIfSomeTeamTrackersDoNotHaveSemanticStatusDefined(): void
    {
        $tracker                   = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_increment_tracker = new ProgramTracker($tracker);

        $top_planning_tracker_semantic_status = $this->createMock(Tracker_Semantic_Status::class);
        $this->semantic_status_factory->method('getByTracker')
            ->with($tracker)
            ->willReturn($top_planning_tracker_semantic_status);

        $list_field = $this->createMock(Tracker_FormElement_Field_List::class);
        $top_planning_tracker_semantic_status->method('getField')
            ->willReturn($list_field);

        $this->semantic_status_dao->method('getNbOfTrackerWithoutSemanticStatusDefined')
            ->with([123, 124])
            ->willReturn(1);

        self::assertFalse(
            $this->checker->isStatusWellConfigured(
                $program_increment_tracker,
                $this->collection
            )
        );
    }

    public function testItReturnsFalseIfSomeTeamStatusSemanticDoesNotContainTheProgramOpenValue(): void
    {
        $tracker                   = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_increment_tracker = new ProgramTracker($tracker);

        $list_field = $this->createMock(Tracker_FormElement_Field_List::class);

        $top_planning_tracker_semantic_status = $this->createMock(Tracker_Semantic_Status::class);
        $top_planning_tracker_semantic_status->method('getField')
            ->willReturn($list_field);

        $this->semantic_status_dao->method('getNbOfTrackerWithoutSemanticStatusDefined')
            ->with([123, 124])
            ->willReturn(0);

        $top_planning_tracker_semantic_status->expects(self::once())
            ->method('getOpenLabels')
            ->willReturn(['open', 'review']);

        $tracker_01_semantic_status = $this->createMock(Tracker_Semantic_Status::class);
        $this->semantic_status_factory->method('getByTracker')
            ->withConsecutive([$tracker], [$this->tracker_team_01])
            ->willReturnOnConsecutiveCalls($top_planning_tracker_semantic_status, $tracker_01_semantic_status);

        $tracker_01_semantic_status->expects(self::once())
            ->method('getOpenLabels')
            ->willReturn(['open']);

        self::assertFalse(
            $this->checker->isStatusWellConfigured(
                $program_increment_tracker,
                $this->collection
            )
        );
    }
}
