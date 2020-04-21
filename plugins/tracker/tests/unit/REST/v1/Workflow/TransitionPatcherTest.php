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
 */

namespace Tuleap\Tracker\REST\v1\Workflow;

require_once __DIR__ . '/../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Workflow\SimpleMode\State\State;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionUpdater;
use Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdater;

class TransitionPatcherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var TransitionPatcher */
    private $patcher;
    /** @var Mockery\MockInterface */
    private $updater;

    private $state_factory;
    private $transition_updater;

    protected function setUp(): void
    {
        $this->updater            = Mockery::mock(ConditionsUpdater::class);
        $this->state_factory      = Mockery::mock(StateFactory::class);
        $this->transition_updater = Mockery::mock(TransitionUpdater::class);

        $this->patcher = new TransitionPatcher(
            $this->updater,
            new DBTransactionExecutorPassthrough(),
            $this->state_factory,
            $this->transition_updater
        );
    }

    public function testPatchThrowsI18NRestExceptionWhenNoAuthorizedUgroups()
    {
        $transition = Mockery::mock(\Transition::class);
        $patch_representation = new WorkflowTransitionPATCHRepresentation();
        $patch_representation->authorized_user_group_ids = [];
        $patch_representation->not_empty_field_ids = [94];
        $patch_representation->is_comment_required = false;

        $this->expectExceptionCode(400);

        $this->patcher->patch($transition, $patch_representation);
    }

    public function testPatchUpdatesSingleTransitionInAdvancedMode()
    {
        $transition_from_advanced_workflow = $this->buildTransitionWithWorkflowMode(true);
        $patch_representation = new WorkflowTransitionPATCHRepresentation();
        $patch_representation->authorized_user_group_ids = ['704', '703_3'];
        $patch_representation->not_empty_field_ids = [23];
        $patch_representation->is_comment_required = true;

        $this->updater
            ->shouldReceive('update')
            ->with(
                $transition_from_advanced_workflow,
                Mockery::contains(704, 3),
                $patch_representation->not_empty_field_ids,
                $patch_representation->is_comment_required
            )
            ->once();

        $this->patcher->patch($transition_from_advanced_workflow, $patch_representation);
    }

    public function testPatchUpdatesAllSiblingTransitionsInSimpleMode()
    {
        $transition_from_simple_workflow = $this->buildTransitionWithWorkflowMode(false);
        $patch_representation = new WorkflowTransitionPATCHRepresentation();
        $patch_representation->authorized_user_group_ids = ['110_3', '374'];
        $patch_representation->not_empty_field_ids = [30];
        $patch_representation->is_comment_required = true;

        $transition_from_simple_workflow->shouldReceive('getIdTo')->andReturn('999');

        $state = Mockery::mock(State::class);

        $this->state_factory->shouldReceive('getStateFromValueId')
            ->with(Mockery::any(), 999)
            ->once()
            ->andReturn($state);

        $this->transition_updater->shouldReceive('updateStatePreConditions')
            ->with(
                $state,
                Mockery::contains(3, 374),
                $patch_representation->not_empty_field_ids,
                $patch_representation->is_comment_required
            )
            ->once();

        $this->patcher->patch($transition_from_simple_workflow, $patch_representation);
    }

    private function buildTransitionWithWorkflowMode(bool $is_advanced): Mockery\MockInterface
    {
        $workflow = Mockery::mock(\Workflow::class)
            ->shouldReceive('isAdvanced')
            ->andReturn($is_advanced)
            ->getMock();
        return $transition = Mockery::mock(\Transition::class)
            ->shouldReceive('getWorkflow')
            ->andReturn($workflow)
            ->getMock();
    }
}
