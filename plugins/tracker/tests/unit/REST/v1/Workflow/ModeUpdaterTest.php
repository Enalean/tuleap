<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\State;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\TransitionReplicator;
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
    private $frozen_fields_dao;
    private $hidden_fieldsets_dao;
    private $tracker;
    private $workflow;
    private $state_factory;
    private $reference_transition_extractor;

    protected function setUp(): void
    {
        $this->workflow_dao                   = Mockery::mock(Workflow_Dao::class);
        $this->transition_replicator          = Mockery::mock(TransitionReplicator::class);
        $this->frozen_fields_dao              = Mockery::mock(FrozenFieldsDao::class);
        $this->hidden_fieldsets_dao           = Mockery::mock(HiddenFieldsetsDao::class);
        $this->state_factory                  = Mockery::mock(StateFactory::class);
        $this->reference_transition_extractor = new TransitionExtractor();

        $this->workflow_mode_updater = new ModeUpdater(
            $this->workflow_dao,
            $this->transition_replicator,
            $this->frozen_fields_dao,
            $this->hidden_fieldsets_dao,
            $this->state_factory,
            $this->reference_transition_extractor
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
        $this->frozen_fields_dao->shouldReceive('deleteAllPostActionsForWorkflow')->with(25)->once();
        $this->hidden_fieldsets_dao->shouldReceive('deleteAllPostActionsForWorkflow')->with(25)->once();

        $this->workflow_mode_updater->switchWorkflowToAdvancedMode($this->tracker);
    }

    public function testItDoesNotSwitchToAdvancedModeIfWorkflowAlreadyInAdvancedMode()
    {
        $this->tracker->shouldReceive('getWorkflow')->andReturn($this->workflow);
        $this->workflow->shouldReceive('getId')->andReturn(25);
        $this->workflow->shouldReceive('isAdvanced')->andReturn(true);

        $this->workflow_dao->shouldReceive('switchWorkflowToAdvancedMode')->with(25)->never();
        $this->frozen_fields_dao->shouldReceive('deleteAllPostActionsForWorkflow')->with(25)->never();
        $this->hidden_fieldsets_dao->shouldReceive('deleteAllPostActionsForWorkflow')->with(25)->never();

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
        $state_open      = new State(1, [$transition_new_open]);
        $state_closed    = new State(2, [$transition_new_closed, $transition_open_closed]);
        $state_cancelled = new State(3, [$transition_open_cancelled, $transition_closed_cancelled]);

        $this->state_factory->shouldReceive('getAllStatesForWorkflow')
            ->with($this->workflow)
            ->once()
            ->andReturn([$state_open, $state_closed, $state_cancelled]);

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
