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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker;
use Tracker_FormElement_Field_List;
use Tracker_Semantic_Status;
use Tracker_Semantic_StatusDao;
use Tracker_Semantic_StatusFactory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\ProgramIncrementsTrackerCollection;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\TrackerColor;

final class StatusSemanticCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var StatusSemanticChecker
     */
    private $checker;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Semantic_StatusDao
     */
    private $semantic_status_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Semantic_StatusFactory
     */
    private $semantic_status_factory;

    /**
     * @var ProgramIncrementsTrackerCollection
     */
    private $collection;

    /**
     * @var Tracker
     */
    private $tracker_team_01;

    /**
     * @var Tracker
     */
    private $tracker_team_02;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semantic_status_dao     = Mockery::mock(Tracker_Semantic_StatusDao::class);
        $this->semantic_status_factory = Mockery::mock(Tracker_Semantic_StatusFactory::class);

        $this->checker = new StatusSemanticChecker(
            $this->semantic_status_dao,
            $this->semantic_status_factory
        );

        $this->tracker_team_01 = new Tracker(123, null, null, null, null, null, null, null, null, null, null, null, null, TrackerColor::default(), null);
        $this->tracker_team_02 = new Tracker(124, null, null, null, null, null, null, null, null, null, null, null, null, TrackerColor::default(), null);
        $this->collection      = new ProgramIncrementsTrackerCollection(
            [
                new ProgramTracker($this->tracker_team_01),
                new ProgramTracker($this->tracker_team_02)
            ]
        );
    }

    public function testItReturnsTrueIfAllStatusSemanticAreWellConfigured(): void
    {
        $tracker                   = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_increment_tracker = new ProgramTracker($tracker);

        $top_planning_tracker_semantic_status = Mockery::mock(Tracker_Semantic_Status::class);
        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->with($tracker)
            ->andReturn($top_planning_tracker_semantic_status);

        $list_field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $top_planning_tracker_semantic_status->shouldReceive('getField')
            ->andReturn($list_field);

        $this->semantic_status_dao->shouldReceive('getNbOfTrackerWithoutSemanticStatusDefined')
            ->with([123, 124])
            ->andReturn(0);

        $top_planning_tracker_semantic_status->shouldReceive('getOpenLabels')
            ->once()
            ->andReturn(['open', 'review']);

        $tracker_01_semantic_status = Mockery::mock(Tracker_Semantic_Status::class);
        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->with($this->tracker_team_01)
            ->andReturn($tracker_01_semantic_status);

        $tracker_01_semantic_status->shouldReceive('getOpenLabels')
            ->once()
            ->andReturn(['open', 'review']);

        $tracker_02_semantic_status = Mockery::mock(Tracker_Semantic_Status::class);
        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->with($this->tracker_team_02)
            ->andReturn($tracker_02_semantic_status);

        $tracker_02_semantic_status->shouldReceive('getOpenLabels')
            ->once()
            ->andReturn(['open', 'in progress', 'review']);

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

        $top_planning_tracker_semantic_status = Mockery::mock(Tracker_Semantic_Status::class);
        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->with($tracker)
            ->andReturn($top_planning_tracker_semantic_status);

        $top_planning_tracker_semantic_status->shouldReceive('getField')
            ->andReturnNull();

        self::assertFalse(
            $this->checker->isStatusWellConfigured(
                $program_increment_tracker,
                $this->collection
            )
        );
    }

    public function testItReturnsFalseIfSomeCTeamTrackersDoNotHaveSemanticStatusDefined(): void
    {
        $tracker                   = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_increment_tracker = new ProgramTracker($tracker);

        $top_planning_tracker_semantic_status = Mockery::mock(Tracker_Semantic_Status::class);
        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->with($tracker)
            ->andReturn($top_planning_tracker_semantic_status);

        $list_field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $top_planning_tracker_semantic_status->shouldReceive('getField')
            ->andReturn($list_field);

        $this->semantic_status_dao->shouldReceive('getNbOfTrackerWithoutSemanticStatusDefined')
            ->with([123, 124])
            ->andReturn(1);

        self::assertFalse(
            $this->checker->isStatusWellConfigured(
                $program_increment_tracker,
                $this->collection
            )
        );
    }

    public function testItReturnsFalseIfSomeTeamStatusSemanticDoNotContainTheProgramOpenValue(): void
    {
        $tracker                   = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_increment_tracker = new ProgramTracker($tracker);

        $top_planning_tracker_semantic_status = Mockery::mock(Tracker_Semantic_Status::class);
        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->with($tracker)
            ->andReturn($top_planning_tracker_semantic_status);

        $list_field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $top_planning_tracker_semantic_status->shouldReceive('getField')
            ->andReturn($list_field);

        $this->semantic_status_dao->shouldReceive('getNbOfTrackerWithoutSemanticStatusDefined')
            ->with([123, 124])
            ->andReturn(0);

        $top_planning_tracker_semantic_status->shouldReceive('getOpenLabels')
            ->once()
            ->andReturn(['open', 'review']);

        $tracker_01_semantic_status = Mockery::mock(Tracker_Semantic_Status::class);
        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->with($this->tracker_team_01)
            ->andReturn($tracker_01_semantic_status);

        $tracker_01_semantic_status->shouldReceive('getOpenLabels')
            ->once()
            ->andReturn(['open']);

        self::assertFalse(
            $this->checker->isStatusWellConfigured(
                $program_increment_tracker,
                $this->collection
            )
        );
    }
}
