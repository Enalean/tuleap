<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\REST\v1\Workflow;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Transition;
use TransitionFactory;
use Tuleap\Tracker\Workflow\SimpleMode\TransitionReplicator;
use Tuleap\Tracker\Workflow\SimpleMode\TransitionRetriever;
use Workflow;
use Workflow_Dao;

class ModeUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ModeUpdater
     */
    private $workflow_mode_updater;
    private $workflow_dao;
    private $transition_replicator;
    private $transition_factory;
    private $transition_retriever;
    private $tracker;
    private $workflow;

    protected function setUp() :void
    {
        $this->workflow_dao          = Mockery::mock(Workflow_Dao::class);
        $this->transition_factory    = Mockery::mock(TransitionFactory::class);
        $this->transition_retriever  = Mockery::mock(TransitionRetriever::class);
        $this->transition_replicator = Mockery::mock(TransitionReplicator::class);

        $this->workflow_mode_updater = new ModeUpdater(
            $this->workflow_dao,
            $this->transition_factory,
            $this->transition_retriever,
            $this->transition_replicator
        );

        $this->tracker  = Mockery::mock(Tracker::class);
        $this->workflow = Mockery::mock(Workflow::class);
    }

    public function testItSwitchesToAdvancedMode()
    {
        $this->tracker->shouldReceive('getWorkflow')->andReturn($this->workflow);
        $this->workflow->shouldReceive('getId')->andReturn(25);
        $this->workflow->shouldReceive('isAdvanced')->andReturn(false);

        $this->workflow_dao->shouldReceive('switchWorkflowToAdvancedMode')->with(25)->once();

        $this->workflow_mode_updater->switchWorkflowToAdvancedMode($this->tracker);
    }

    public function testItDoesNotSwitchToAdvancedModeIfWorkflowAlreadyInAdvancedMode()
    {
        $this->tracker->shouldReceive('getWorkflow')->andReturn($this->workflow);
        $this->workflow->shouldReceive('getId')->andReturn(25);
        $this->workflow->shouldReceive('isAdvanced')->andReturn(true);

        $this->workflow_dao->shouldReceive('switchWorkflowToAdvancedMode')->with(25)->never();

        $this->workflow_mode_updater->switchWorkflowToAdvancedMode($this->tracker);
    }

    public function testItSwitchesToSimpleMode()
    {
        $this->tracker->shouldReceive('getWorkflow')->andReturn($this->workflow);
        $this->workflow->shouldReceive('getId')->andReturn(25);
        $this->workflow->shouldReceive('isAdvanced')->andReturn(true);

        $open_value      = new Tracker_FormElement_Field_List_Bind_StaticValue(1, 'open', '', 1, false);
        $closed_value    = new Tracker_FormElement_Field_List_Bind_StaticValue(2, 'closed', '', 2, false);
        $cancelled_value = new Tracker_FormElement_Field_List_Bind_StaticValue(3, 'cancelled', '', 3, false);

        $transition_new_open         = new Transition(1, 25, null, $open_value);
        $transition_new_closed       = new Transition(2, 25, null, $closed_value);
        $transition_open_closed      = new Transition(3, 25, $open_value, $closed_value);
        $transition_open_cancelled   = new Transition(4, 25, $open_value, $cancelled_value);
        $transition_closed_cancelled = new Transition(5, 25, $closed_value, $cancelled_value);

        $this->transition_factory->shouldReceive('getTransitions')
            ->with($this->workflow)
            ->andReturn([
                $transition_new_open,
                $transition_new_closed,
                $transition_open_closed,
                $transition_open_cancelled,
                $transition_closed_cancelled
            ]);

        $this->transition_retriever->shouldReceive('getFirstSiblingTransition')
            ->with($transition_new_open)
            ->andReturn($transition_new_open)
            ->once();

        $this->transition_retriever->shouldReceive('getFirstSiblingTransition')
            ->with($transition_new_closed)
            ->andReturn($transition_open_closed)
            ->once();

        $this->transition_retriever->shouldReceive('getFirstSiblingTransition')
            ->with($transition_open_cancelled)
            ->andReturn($transition_open_cancelled)
            ->once();

        $this->transition_replicator->shouldReceive('replicate')
            ->with($transition_new_open, Mockery::any())
            ->never();

        $this->transition_replicator->shouldReceive('replicate')
            ->with($transition_open_closed, $transition_new_closed)
            ->once();

        $this->transition_replicator->shouldReceive('replicate')
            ->with($transition_open_cancelled, $transition_closed_cancelled)
            ->once();

        $this->workflow_dao->shouldReceive('switchWorkflowToSimpleMode')->with(25)->once();

        $this->workflow_mode_updater->switchWorkflowToSimpleMode($this->tracker);
    }

    public function testItDoesNotSwitchToSimpleModeIfWorkflowAlreadyInSimpleMode()
    {
        $this->tracker->shouldReceive('getWorkflow')->andReturn($this->workflow);
        $this->workflow->shouldReceive('getId')->andReturn(25);
        $this->workflow->shouldReceive('isAdvanced')->andReturn(false);

        $this->workflow_dao->shouldReceive('switchWorkflowToSimpleMode')->with(25)->never();
        $this->transition_replicator->shouldReceive('replicate')->never();

        $this->workflow_mode_updater->switchWorkflowToSimpleMode($this->tracker);
    }
}
