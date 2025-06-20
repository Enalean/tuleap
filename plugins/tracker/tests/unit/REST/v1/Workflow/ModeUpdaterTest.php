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

declare(strict_types=1);

namespace Tuleap\Tracker\REST\v1\Workflow;

use PHPUnit\Framework\MockObject\MockObject;
use Transition;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\State;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\TransitionReplicator;
use Workflow;
use Workflow_Dao;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ModeUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ModeUpdater $workflow_mode_updater;
    private Workflow_Dao&MockObject $workflow_dao;
    private TransitionReplicator&MockObject $transition_replicator;
    private FrozenFieldsDao&MockObject $frozen_fields_dao;
    private HiddenFieldsetsDao&MockObject $hidden_fieldsets_dao;
    private Tracker&MockObject $tracker;
    private Workflow&MockObject $workflow;
    private StateFactory&MockObject $state_factory;
    private TransitionExtractor $reference_transition_extractor;

    protected function setUp(): void
    {
        $this->workflow_dao                   = $this->createMock(Workflow_Dao::class);
        $this->transition_replicator          = $this->createMock(TransitionReplicator::class);
        $this->frozen_fields_dao              = $this->createMock(FrozenFieldsDao::class);
        $this->hidden_fieldsets_dao           = $this->createMock(HiddenFieldsetsDao::class);
        $this->state_factory                  = $this->createMock(StateFactory::class);
        $this->reference_transition_extractor = new TransitionExtractor();

        $this->workflow_mode_updater = new ModeUpdater(
            $this->workflow_dao,
            $this->transition_replicator,
            $this->frozen_fields_dao,
            $this->hidden_fieldsets_dao,
            $this->state_factory,
            $this->reference_transition_extractor
        );

        $this->tracker  = $this->createMock(Tracker::class);
        $this->workflow = $this->createMock(Workflow::class);
    }

    public function testItSwitchesToAdvancedMode(): void
    {
        $this->tracker->method('getWorkflow')->willReturn($this->workflow);
        $this->workflow->method('getId')->willReturn(25);
        $this->workflow->method('isAdvanced')->willReturn(false);

        $this->workflow_dao->expects($this->once())->method('switchWorkflowToAdvancedMode')->with(25);
        $this->frozen_fields_dao->expects($this->once())->method('deleteAllPostActionsForWorkflow')->with(25);
        $this->hidden_fieldsets_dao->expects($this->once())->method('deleteAllPostActionsForWorkflow')->with(25);

        $this->workflow_mode_updater->switchWorkflowToAdvancedMode($this->tracker);
    }

    public function testItDoesNotSwitchToAdvancedModeIfWorkflowAlreadyInAdvancedMode(): void
    {
        $this->tracker->method('getWorkflow')->willReturn($this->workflow);
        $this->workflow->method('getId')->willReturn(25);
        $this->workflow->method('isAdvanced')->willReturn(true);

        $this->workflow_dao->expects($this->never())->method('switchWorkflowToAdvancedMode')->with(25);
        $this->frozen_fields_dao->expects($this->never())->method('deleteAllPostActionsForWorkflow')->with(25);
        $this->hidden_fieldsets_dao->expects($this->never())->method('deleteAllPostActionsForWorkflow')->with(25);

        $this->workflow_mode_updater->switchWorkflowToAdvancedMode($this->tracker);
    }

    public function testItSwitchesToSimpleMode(): void
    {
        $this->tracker->method('getWorkflow')->willReturn($this->workflow);
        $this->workflow->method('getId')->willReturn(25);
        $this->workflow->method('isAdvanced')->willReturn(true);

        $open_value      = ListStaticValueBuilder::aStaticValue('open')->withId(1)->build();
        $closed_value    = ListStaticValueBuilder::aStaticValue('closed')->withId(2)->build();
        $cancelled_value = ListStaticValueBuilder::aStaticValue('cancelled')->withId(3)->build();

        $transition_new_open         = new Transition(1, 25, null, $open_value);
        $transition_new_closed       = new Transition(2, 25, null, $closed_value);
        $transition_open_closed      = new Transition(3, 25, $open_value, $closed_value);
        $transition_open_cancelled   = new Transition(4, 25, $open_value, $cancelled_value);
        $transition_closed_cancelled = new Transition(5, 25, $closed_value, $cancelled_value);
        $state_open                  = new State(1, [$transition_new_open]);
        $state_closed                = new State(2, [$transition_new_closed, $transition_open_closed]);
        $state_cancelled             = new State(3, [$transition_open_cancelled, $transition_closed_cancelled]);

        $this->state_factory->expects($this->once())->method('getAllStatesForWorkflow')
            ->with($this->workflow)
            ->willReturn([$state_open, $state_closed, $state_cancelled]);

        $this->transition_replicator->expects($this->exactly(2))->method('replicate')
            ->willReturnCallback(static fn (Transition $from, Transition $to) => match (true) {
                $from === $transition_open_closed && $to === $transition_new_closed,
                $from === $transition_open_cancelled && $to === $transition_closed_cancelled => true
            });

        $this->workflow_dao->expects($this->once())->method('switchWorkflowToSimpleMode')->with(25);

        $this->workflow_mode_updater->switchWorkflowToSimpleMode($this->tracker);
    }

    public function testItDoesNotSwitchToSimpleModeIfWorkflowAlreadyInSimpleMode(): void
    {
        $this->tracker->method('getWorkflow')->willReturn($this->workflow);
        $this->workflow->method('getId')->willReturn(25);
        $this->workflow->method('isAdvanced')->willReturn(false);

        $this->workflow_dao->expects($this->never())->method('switchWorkflowToSimpleMode')->with(25);
        $this->transition_replicator->expects($this->never())->method('replicate');

        $this->workflow_mode_updater->switchWorkflowToSimpleMode($this->tracker);
    }
}
