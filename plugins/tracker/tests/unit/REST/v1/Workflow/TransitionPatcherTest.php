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

declare(strict_types=1);

namespace Tuleap\Tracker\REST\v1\Workflow;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Workflow\SimpleMode\State\State;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionUpdater;
use Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdater;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TransitionPatcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TransitionPatcher $patcher;
    private ConditionsUpdater&MockObject $updater;

    private StateFactory&MockObject $state_factory;
    private TransitionUpdater&MockObject $transition_updater;

    protected function setUp(): void
    {
        $this->updater            = $this->createMock(ConditionsUpdater::class);
        $this->state_factory      = $this->createMock(StateFactory::class);
        $this->transition_updater = $this->createMock(TransitionUpdater::class);

        $this->patcher = new TransitionPatcher(
            $this->updater,
            new DBTransactionExecutorPassthrough(),
            $this->state_factory,
            $this->transition_updater
        );
    }

    public function testPatchThrowsI18NRestExceptionWhenNoAuthorizedUgroups(): void
    {
        $transition                                      = $this->createMock(\Transition::class);
        $patch_representation                            = new WorkflowTransitionPATCHRepresentation();
        $patch_representation->authorized_user_group_ids = [];
        $patch_representation->not_empty_field_ids       = [94];
        $patch_representation->is_comment_required       = false;

        $this->expectExceptionCode(400);

        $this->patcher->patch($transition, $patch_representation);
    }

    public function testPatchUpdatesSingleTransitionInAdvancedMode(): void
    {
        $transition_from_advanced_workflow               = $this->buildTransitionWithWorkflowMode(true);
        $patch_representation                            = new WorkflowTransitionPATCHRepresentation();
        $patch_representation->authorized_user_group_ids = ['704', '703_3'];
        $patch_representation->not_empty_field_ids       = [23];
        $patch_representation->is_comment_required       = true;

        $this->updater->expects($this->once())
            ->method('update')
            ->with(
                $transition_from_advanced_workflow,
                ['704', '3'],
                $patch_representation->not_empty_field_ids,
                $patch_representation->is_comment_required,
            );

        $this->patcher->patch($transition_from_advanced_workflow, $patch_representation);
    }

    public function testPatchUpdatesAllSiblingTransitionsInSimpleMode(): void
    {
        $transition_from_simple_workflow                 = $this->buildTransitionWithWorkflowMode(false);
        $patch_representation                            = new WorkflowTransitionPATCHRepresentation();
        $patch_representation->authorized_user_group_ids = ['110_3', '374'];
        $patch_representation->not_empty_field_ids       = [30];
        $patch_representation->is_comment_required       = true;

        $transition_from_simple_workflow->method('getIdTo')->willReturn('999');

        $state = $this->createMock(State::class);

        $this->state_factory->expects($this->once())
            ->method('getStateFromValueId')
            ->with($this->anything(), 999)
            ->willReturn($state);

        $this->transition_updater->expects($this->once())
            ->method('updateStatePreConditions')
            ->with(
                $state,
                ['3', '374'],
                $patch_representation->not_empty_field_ids,
                $patch_representation->is_comment_required,
            );

        $this->patcher->patch($transition_from_simple_workflow, $patch_representation);
    }

    private function buildTransitionWithWorkflowMode(bool $is_advanced): \Transition&MockObject
    {
        $workflow = $this->createMock(\Workflow::class);
        $workflow->method('isAdvanced')->willReturn($is_advanced);

        $transition = $this->createMock(\Transition::class);
        $transition->method('getWorkflow')->willReturn($workflow);

        return $transition;
    }
}
