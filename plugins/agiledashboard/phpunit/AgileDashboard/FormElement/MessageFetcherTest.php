<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement;

use AgileDashboard_Semantic_InitialEffortFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\AgileDashboard\Semantic\SemanticDone;
use Tuleap\AgileDashboard\Semantic\SemanticDoneFactory;

final class MessageFetcherTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElement_Field
     */
    private $field;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker
     */
    private $backlog_tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker
     */
    private $tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\PlanningFactory
     */
    private $planning_factory;
    /**
     * @var MessageFetcher
     */
    private $message_fetcher;
    /**
     * @var \Mockery\MockInterface|AgileDashboard_Semantic_InitialEffortFactory
     */
    private $initial_effort_factory;
    /**
     * @var \Mockery\MockInterface|SemanticDoneFactory
     */
    private $semantic_done_factory;

    protected function setUp(): void
    {
        $this->planning_factory       = \Mockery::spy(\PlanningFactory::class);
        $this->initial_effort_factory = \Mockery::mock('AgileDashboard_Semantic_InitialEffortFactory');
        $this->semantic_done_factory  = \Mockery::mock('Tuleap\AgileDashboard\Semantic\SemanticDoneFactory');

        $this->message_fetcher = new MessageFetcher(
            $this->planning_factory,
            $this->initial_effort_factory,
            $this->semantic_done_factory
        );

        $this->tracker          = \Mockery::mock(\Tracker::class);
        $this->backlog_tracker  = \Mockery::mock(\Tracker::class);
        $this->backlog_tracker->shouldReceive('getName');
        $this->field            = \Mockery::mock(\Tracker_FormElement_Field::class);
    }

    public function testItDoesNotAddWarningsIfAllIsWellConfigured(): void
    {
        $planning       = $this->getMockedPlanning();
        $semantic_done  = $this->getMockedSemanticDone(true);
        $initial_effort = $this->getMockInitialEffortField();

        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($this->tracker)->andReturns($planning);
        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')->with($this->backlog_tracker)->andReturn($semantic_done);
        $this->initial_effort_factory->shouldReceive('getByTracker')->with($this->backlog_tracker)->andReturn($initial_effort);

        $warnings = $this->message_fetcher->getWarningsRelatedToPlanningConfiguration($this->tracker);

        $this->assertEmpty($warnings);
    }

    public function testItReturnsAWarningIfTrackerIsNotAPlanningTracker(): void
    {
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($this->tracker)->andReturns(null);

        $warnings = $this->message_fetcher->getWarningsRelatedToPlanningConfiguration($this->tracker);

        $this->assertNotEmpty($warnings);
    }

    public function testItReturnsAWarningIfBacklogTrackerDoesNotHaveSemanticDone(): void
    {
        $planning       = $this->getMockedPlanning();
        $semantic_done  = $this->getMockedSemanticDone(false);
        $initial_effort = $this->getMockInitialEffortField();

        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($this->tracker)->andReturns($planning);
        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')->with($this->backlog_tracker)->andReturn($semantic_done);
        $this->initial_effort_factory->shouldReceive('getByTracker')->with($this->backlog_tracker)->andReturn($initial_effort);

        $warnings = $this->message_fetcher->getWarningsRelatedToPlanningConfiguration($this->tracker);

        $this->assertNotEmpty($warnings);
    }

    public function testItReturnsAWarningIfBacklogTrackerDoesNotHaveSemanticInitialEffort(): void
    {
        $planning       = $this->getMockedPlanning();
        $semantic_done  = $this->getMockedSemanticDone(true);
        $initial_effort = \Mockery::spy(\AgileDashBoard_Semantic_InitialEffort::class)
            ->shouldReceive('getField')
            ->andReturns(null)->getMock();

        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')->with($this->tracker)->andReturns($planning);
        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')->with($this->backlog_tracker)->andReturn($semantic_done);
        $this->initial_effort_factory->shouldReceive('getByTracker')->with($this->backlog_tracker)->andReturn($initial_effort);

        $warnings = $this->message_fetcher->getWarningsRelatedToPlanningConfiguration($this->tracker);

        $this->assertNotEmpty($warnings);
    }

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    protected function getMockedPlanning()
    {
        return \Mockery::spy(\Planning::class)
            ->shouldReceive('getBacklogTrackers')
            ->andReturns([$this->backlog_tracker])->getMock();
    }

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    protected function getMockedSemanticDone(bool $is_semantic_defined)
    {
        return \Mockery::spy(SemanticDone::class)
            ->shouldReceive('isSemanticDefined')
            ->andReturns($is_semantic_defined)->getMock();
    }

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    protected function getMockInitialEffortField()
    {
        return \Mockery::spy(\AgileDashBoard_Semantic_InitialEffort::class)
            ->shouldReceive('getField')
            ->andReturns($this->field)->getMock();
    }
}
