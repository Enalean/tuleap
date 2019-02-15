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

namespace Tuleap\Tracker\Workflow\PostAction\Update;

require_once(__DIR__ . "/TransitionFactory.php");

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tuleap\DB\TransactionExecutor;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\CIBuild;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionUpdater;

class PostActionCollectionUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PostActionCollectionUpdater
     */
    private $collection_updater;

    /**
     * @var MockInterface
     */
    private $transaction_executor;

    /**
     * @var MockInterface
     */
    private $post_action_updater1;
    /**
     * @var MockInterface
     */
    private $post_action_updater2;

    /**
     * @before
     */
    public function createUpdater()
    {
        $this->post_action_updater1 = Mockery::mock(PostActionUpdater::class);
        $this->post_action_updater2 = Mockery::mock(PostActionUpdater::class);

        $this->transaction_executor = Mockery::mock(TransactionExecutor::class);
        $this->transaction_executor
            ->shouldReceive('execute')
            ->andReturnUsing(function (callable $operation) {
                $operation();
            });

        $this->collection_updater = new PostActionCollectionUpdater(
            $this->transaction_executor,
            $this->post_action_updater1,
            $this->post_action_updater2
        );
    }

    public function testUpdateByTransitionDelegatesUpdateToUpdaters()
    {
        $transition = TransitionFactory::buildATransition();

        $action            = new CIBuild(2, 'http://example.test');
        $action_collection = new PostActionCollection($action);

        $this->post_action_updater1
            ->shouldReceive('updateByTransition')
            ->with($action_collection, $transition);
        $this->post_action_updater2
            ->shouldReceive('updateByTransition')
            ->with($action_collection, $transition);

        $this->collection_updater->updateByTransition($transition, $action_collection);
    }

    public function testUpdateAllSiblingsTransitionDelegatesUpdateToUpdaters()
    {
        $transition  = TransitionFactory::buildATransition();
        $transition2 = TransitionFactory::buildATransition();
        $transition3 = TransitionFactory::buildATransition();

        $all_transitions = [
            $transition,
            $transition2,
            $transition3
        ];

        $action            = new CIBuild(2, 'http://example.test');
        $action_collection = new PostActionCollection($action);

        $this->post_action_updater1
            ->shouldReceive('updateByTransition')
            ->with($action_collection, $transition);
        $this->post_action_updater2
            ->shouldReceive('updateByTransition')
            ->with($action_collection, $transition);

        $this->post_action_updater1
            ->shouldReceive('updateByTransition')
            ->with($action_collection, $transition2);
        $this->post_action_updater2
            ->shouldReceive('updateByTransition')
            ->with($action_collection, $transition2);

        $this->post_action_updater1
            ->shouldReceive('updateByTransition')
            ->with($action_collection, $transition3);
        $this->post_action_updater2
            ->shouldReceive('updateByTransition')
            ->with($action_collection, $transition3);

        $this->collection_updater->updateForAllSiblingsTransition($all_transitions, $action_collection);
    }
}
