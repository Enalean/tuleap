<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use TuleapTestCase;

require_once __DIR__ . '/../../../bootstrap.php';

class MessageFetcherTest extends TuleapTestCase
{
    /**
     * @var MessageFetcher
     */
    private $message_fetcher;
    /**
     * @var \Mockery\MockInterface|AgileDashboard_Semantic_InitialEffortFactory
     */
    private $initial_effort_factory;
    /**
     * @var \Mockery\MockInterface|Tuleap\AgileDashboard\Semantic\SemanticDoneFactory
     */
    private $semantic_done_factory;

    public function setUp()
    {
        parent::setUp();

        $this->planning_factory       = mock('PlanningFactory');
        $this->initial_effort_factory = \Mockery::mock('AgileDashboard_Semantic_InitialEffortFactory');
        $this->semantic_done_factory  = \Mockery::mock('Tuleap\AgileDashboard\Semantic\SemanticDoneFactory');

        $this->message_fetcher = new MessageFetcher(
            $this->planning_factory,
            $this->initial_effort_factory,
            $this->semantic_done_factory
        );

        $this->tracker          = aMockTracker()->build();
        $this->backlog_tracker  = aMockTracker()->build();
        $this->field            = aMockField()->build();
    }

    public function itDoesNotAddWarningsIfAllIsWellConfigured()
    {
        $planning       = stub('Planning')->getBacklogTrackers()->returns(array($this->backlog_tracker));
        $semantic_done  = stub('Tuleap\AgileDashboard\Semantic\SemanticDone')->isSemanticDefined()->returns(true);
        $initial_effort = stub('AgileDashBoard_Semantic_InitialEffort')->getField()->returns($this->field);

        stub($this->planning_factory)->getPlanningByPlanningTracker($this->tracker)->returns($planning);
        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')->with($this->backlog_tracker)->andReturn($semantic_done);
        $this->initial_effort_factory->shouldReceive('getByTracker')->with($this->backlog_tracker)->andReturn($initial_effort);

        $warnings = $this->message_fetcher->getWarningsRelatedToPlanningConfiguration($this->tracker);

        $this->assertArrayEmpty($warnings);
    }

    public function itReturnsAWarningIfTrackerIsNotAPlanningTracker()
    {
        stub($this->planning_factory)->getPlanningByPlanningTracker($this->tracker)->returns(null);

        $warnings = $this->message_fetcher->getWarningsRelatedToPlanningConfiguration($this->tracker);

        $this->assertArrayNotEmpty($warnings);
    }

    public function itReturnsAWarningIfBacklogTrackerDoesNotHaveSemanticDone()
    {
        $planning       = stub('Planning')->getBacklogTrackers()->returns(array($this->backlog_tracker));
        $semantic_done  = stub('Tuleap\AgileDashboard\Semantic\SemanticDone')->isSemanticDefined()->returns(false);
        $initial_effort = stub('AgileDashBoard_Semantic_InitialEffort')->getField()->returns($this->field);

        stub($this->planning_factory)->getPlanningByPlanningTracker($this->tracker)->returns($planning);
        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')->with($this->backlog_tracker)->andReturn($semantic_done);
        $this->initial_effort_factory->shouldReceive('getByTracker')->with($this->backlog_tracker)->andReturn($initial_effort);

        $warnings = $this->message_fetcher->getWarningsRelatedToPlanningConfiguration($this->tracker);

        $this->assertArrayNotEmpty($warnings);
    }

    public function itReturnsAWarningIfBacklogTrackerDoesNotHaveSemanticInitialEffort()
    {
        $planning       = stub('Planning')->getBacklogTrackers()->returns(array($this->backlog_tracker));
        $semantic_done  = stub('Tuleap\AgileDashboard\Semantic\SemanticDone')->isSemanticDefined()->returns(true);
        $initial_effort = stub('AgileDashBoard_Semantic_InitialEffort')->getField()->returns(null);

        stub($this->planning_factory)->getPlanningByPlanningTracker($this->tracker)->returns($planning);
        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')->with($this->backlog_tracker)->andReturn($semantic_done);
        $this->initial_effort_factory->shouldReceive('getByTracker')->with($this->backlog_tracker)->andReturn($initial_effort);

        $warnings = $this->message_fetcher->getWarningsRelatedToPlanningConfiguration($this->tracker);

        $this->assertArrayNotEmpty($warnings);
    }
}
