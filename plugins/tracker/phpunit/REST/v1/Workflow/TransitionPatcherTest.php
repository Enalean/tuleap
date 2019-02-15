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
 */

namespace Tuleap\Tracker\REST\v1\Workflow;

require_once __DIR__ . '/../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\DB\TransactionExecutor;
use Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdater;
use Tuleap\Tracker\Workflow\Transition\NoSiblingTransitionException;
use Tuleap\Tracker\Workflow\Transition\Update\TransitionCollection;
use Tuleap\Tracker\Workflow\Transition\Update\TransitionRetriever;

class TransitionPatcherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var TransitionPatcher */
    private $patcher;
    /** @var Mockery\MockInterface */
    private $updater;
    /** @var Mockery\MockInterface */
    private $retriever;
    /** @var Mockery\MockInterface */
    private $transaction_executor;

    protected function setUp(): void
    {
        $this->updater              = Mockery::mock(ConditionsUpdater::class);
        $this->retriever            = Mockery::mock(TransitionRetriever::class);
        $this->transaction_executor = Mockery::mock(TransactionExecutor::class);
        $this->patcher              = new TransitionPatcher(
            $this->updater,
            $this->retriever,
            $this->transaction_executor
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
        $this->mockTransactionExecutor();
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
        $this->mockTransactionExecutor();
        $transition_from_simple_workflow = $this->buildTransitionWithWorkflowMode(false);
        $patch_representation = new WorkflowTransitionPATCHRepresentation();
        $patch_representation->authorized_user_group_ids = ['110_3', '374'];
        $patch_representation->not_empty_field_ids = [30];
        $patch_representation->is_comment_required = true;

        $this->updater
            ->shouldReceive('update')
            ->with(
                Mockery::type(\Transition::class),
                Mockery::contains(3, 374),
                $patch_representation->not_empty_field_ids,
                $patch_representation->is_comment_required
            )->times(3);

        $first_sibling_transition  = Mockery::mock(\Transition::class);
        $second_sibling_transition = Mockery::mock(\Transition::class);
        $this->retriever
            ->shouldReceive('getSiblingTransitions')
            ->andReturn(new TransitionCollection($first_sibling_transition, $second_sibling_transition));

        $this->patcher->patch($transition_from_simple_workflow, $patch_representation);
    }

    public function testPatchIgnoresNoSiblingTransitionException()
    {
        $this->mockTransactionExecutor();
        $transition_from_simple_workflow = $this->buildTransitionWithWorkflowMode(false);
        $patch_representation = new WorkflowTransitionPATCHRepresentation();
        $patch_representation->authorized_user_group_ids = ['110_3', '374'];
        $patch_representation->not_empty_field_ids = [30];
        $patch_representation->is_comment_required = true;

        $this->updater
            ->shouldReceive('update')
            ->with(
                $transition_from_simple_workflow,
                Mockery::contains(3, 374),
                $patch_representation->not_empty_field_ids,
                $patch_representation->is_comment_required
            )->once();

        $this->retriever
            ->shouldReceive('getSiblingTransitions')
            ->andThrow(new NoSiblingTransitionException());

        $this->patcher->patch($transition_from_simple_workflow, $patch_representation);
    }

    private function mockTransactionExecutor()
    {
        $this->transaction_executor
            ->shouldReceive('execute')
            ->andReturnUsing(
                function (callable $operation) {
                    $operation();
                }
            );
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
