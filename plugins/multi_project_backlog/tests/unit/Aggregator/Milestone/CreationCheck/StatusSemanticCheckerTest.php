<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\CreationCheck;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Planning;
use Planning_VirtualTopMilestone;
use Tracker;
use Tracker_FormElement_Field_List;
use Tracker_Semantic_Status;
use Tracker_Semantic_StatusDao;
use Tracker_Semantic_StatusFactory;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerCollection;
use Tuleap\Tracker\TrackerColor;

class StatusSemanticCheckerTest extends TestCase
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
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning_VirtualTopMilestone
     */
    private $top_milestone;

    /**
     * @var MilestoneTrackerCollection
     */
    private $collection;

    /**
     * @var Tracker
     */
    private $tracker_contributor_01;

    /**
     * @var Tracker
     */
    private $tracker_contributor_02;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semantic_status_dao     = Mockery::mock(Tracker_Semantic_StatusDao::class);
        $this->semantic_status_factory = Mockery::mock(Tracker_Semantic_StatusFactory::class);

        $this->checker = new StatusSemanticChecker(
            $this->semantic_status_dao,
            $this->semantic_status_factory
        );

        $this->top_milestone = Mockery::mock(Planning_VirtualTopMilestone::class);

        $this->tracker_contributor_01 = new Tracker(123, null, null, null, null, null, null, null, null, null, null, null, null, TrackerColor::default(), null);
        $this->tracker_contributor_02 = new Tracker(124, null, null, null, null, null, null, null, null, null, null, null, null, TrackerColor::default(), null);
        $this->collection             = new MilestoneTrackerCollection([
            $this->tracker_contributor_01,
            $this->tracker_contributor_02
        ]);
    }

    public function testItReturnsTrueIfAllStatusSemanticAreWellConfigured(): void
    {
        $top_planning = Mockery::mock(Planning::class);
        $this->top_milestone->shouldReceive('getPlanning')
            ->once()
            ->andReturn($top_planning);

        $top_planning_tracker = new Tracker(104, null, null, null, null, null, null, null, null, null, null, null, null, TrackerColor::default(), null);
        $top_planning->shouldReceive('getPlanningTracker')
            ->once()
            ->andReturn($top_planning_tracker);

        $top_planning_tracker_semantic_status = Mockery::mock(Tracker_Semantic_Status::class);
        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->with($top_planning_tracker)
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
            ->with($this->tracker_contributor_01)
            ->andReturn($tracker_01_semantic_status);

        $tracker_01_semantic_status->shouldReceive('getOpenLabels')
            ->once()
            ->andReturn(['open', 'review']);

        $tracker_02_semantic_status = Mockery::mock(Tracker_Semantic_Status::class);
        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->with($this->tracker_contributor_02)
            ->andReturn($tracker_02_semantic_status);

        $tracker_02_semantic_status->shouldReceive('getOpenLabels')
            ->once()
            ->andReturn(['open', 'in progress', 'review']);

        $this->assertTrue(
            $this->checker->areSemanticStatusWellConfigured(
                $this->top_milestone,
                $this->collection
            )
        );
    }

    public function testItReturnsFalseIfAggregatorTrackerDoesNotHaveStatusSemantic(): void
    {
        $top_planning = Mockery::mock(Planning::class);
        $this->top_milestone->shouldReceive('getPlanning')
            ->once()
            ->andReturn($top_planning);

        $top_planning_tracker = new Tracker(104, null, null, null, null, null, null, null, null, null, null, null, null, TrackerColor::default(), null);
        $top_planning->shouldReceive('getPlanningTracker')
            ->once()
            ->andReturn($top_planning_tracker);

        $top_planning_tracker_semantic_status = Mockery::mock(Tracker_Semantic_Status::class);
        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->with($top_planning_tracker)
            ->andReturn($top_planning_tracker_semantic_status);

        $top_planning_tracker_semantic_status->shouldReceive('getField')
            ->andReturnNull();

        $this->assertFalse(
            $this->checker->areSemanticStatusWellConfigured(
                $this->top_milestone,
                $this->collection
            )
        );
    }

    public function testItReturnsFalseIfSomeContributorTrackersDoNotHaveSemanticStatusDefined(): void
    {
        $top_planning = Mockery::mock(Planning::class);
        $this->top_milestone->shouldReceive('getPlanning')
            ->once()
            ->andReturn($top_planning);

        $top_planning_tracker = new Tracker(104, null, null, null, null, null, null, null, null, null, null, null, null, TrackerColor::default(), null);
        $top_planning->shouldReceive('getPlanningTracker')
            ->once()
            ->andReturn($top_planning_tracker);

        $top_planning_tracker_semantic_status = Mockery::mock(Tracker_Semantic_Status::class);
        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->with($top_planning_tracker)
            ->andReturn($top_planning_tracker_semantic_status);

        $list_field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $top_planning_tracker_semantic_status->shouldReceive('getField')
            ->andReturn($list_field);

        $this->semantic_status_dao->shouldReceive('getNbOfTrackerWithoutSemanticStatusDefined')
            ->with([123, 124])
            ->andReturn(1);

        $this->assertFalse(
            $this->checker->areSemanticStatusWellConfigured(
                $this->top_milestone,
                $this->collection
            )
        );
    }

    public function testItReturnsFalseIfSomeContributorStatusSemanticDoNotContainTheAggregatorOpenValue(): void
    {
        $top_planning = Mockery::mock(Planning::class);
        $this->top_milestone->shouldReceive('getPlanning')
            ->once()
            ->andReturn($top_planning);

        $top_planning_tracker = new Tracker(104, null, null, null, null, null, null, null, null, null, null, null, null, TrackerColor::default(), null);
        $top_planning->shouldReceive('getPlanningTracker')
            ->once()
            ->andReturn($top_planning_tracker);

        $top_planning_tracker_semantic_status = Mockery::mock(Tracker_Semantic_Status::class);
        $this->semantic_status_factory->shouldReceive('getByTracker')
            ->with($top_planning_tracker)
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
            ->with($this->tracker_contributor_01)
            ->andReturn($tracker_01_semantic_status);

        $tracker_01_semantic_status->shouldReceive('getOpenLabels')
            ->once()
            ->andReturn(['open']);

        $this->assertFalse(
            $this->checker->areSemanticStatusWellConfigured(
                $this->top_milestone,
                $this->collection
            )
        );
    }
}
