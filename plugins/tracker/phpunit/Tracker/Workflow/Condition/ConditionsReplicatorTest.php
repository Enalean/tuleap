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

declare(strict_types=1);

namespace Tuleap\Tracker\Workflow\Transition\Condition;

require_once __DIR__ . '/../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Workflow\Transition\NoSiblingTransitionException;
use Tuleap\Tracker\Workflow\Transition\Update\TransitionRetriever;

class ConditionsReplicatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var ConditionsReplicator */
    private $conditions_replicator;
    /** @var Mockery\MockInterface */
    private $transition_retriever;
    /** @var Mockery\MockInterface */
    private $condition_factory;
    /** @var Mockery\MockInterface */
    private $conditions_updater;

    protected function setUp(): void
    {
        $this->transition_retriever  = Mockery::mock(TransitionRetriever::class);
        $this->condition_factory     = Mockery::mock(\Workflow_Transition_ConditionFactory::class);
        $this->conditions_updater    = Mockery::mock(ConditionsUpdater::class);
        $this->conditions_replicator = new ConditionsReplicator(
            $this->transition_retriever,
            $this->condition_factory,
            $this->conditions_updater
        );
    }

    public function testReplicateFromFirstSiblingTransition()
    {
        $transition         = Mockery::mock(\Transition::class);
        $sibling_transition = Mockery::mock(\Transition::class);
        $this->transition_retriever
            ->shouldReceive('getFirstSiblingTransition')
            ->with($transition)
            ->andReturn($sibling_transition);
        $not_empty_ids       = [195, 305];
        $not_empty_condition = Mockery::mock(\Workflow_Transition_Condition_FieldNotEmpty::class)
            ->shouldReceive('getFieldIds')
            ->andReturn($not_empty_ids)
            ->getMock();
        $this->condition_factory
            ->shouldReceive('getFieldNotEmptyCondition')
            ->andReturn($not_empty_condition);
        $is_comment_required = true;
        $comment_condition = Mockery::mock(\Workflow_Transition_Condition_CommentNotEmpty::class)
            ->shouldReceive('isCommentRequired')
            ->andReturn($is_comment_required)
            ->getMock();
        $this->condition_factory
            ->shouldReceive('getCommentNotEmptyCondition')
            ->with($sibling_transition)
            ->andReturn($comment_condition);
        $permission_condition = Mockery::mock(\Workflow_Transition_Condition_Permissions::class)
            ->shouldReceive('getAuthorizedUGroupsAsArray')
            ->andReturn([['ugroup_id' => '191'], ['ugroup_id' => '154_3']])
            ->getMock();
        $this->condition_factory
            ->shouldReceive('getPermissionsCondition')
            ->andReturn($permission_condition);

        $this->conditions_updater
            ->shouldReceive('update')
            ->with($transition, ['191', '154_3'], $not_empty_ids, $is_comment_required);

        $this->conditions_replicator->replicateFromFirstSiblingTransition($transition);
    }

    public function testReplicateFromFirstSiblingTransitionDoesNotUpdateWhenNoSibling()
    {
        $this->transition_retriever
            ->shouldReceive('getFirstSiblingTransition')
            ->andThrow(new NoSiblingTransitionException());
        $this->conditions_updater->shouldNotReceive('update');

        $this->conditions_replicator->replicateFromFirstSiblingTransition(Mockery::mock(\Transition::class));
    }
}
