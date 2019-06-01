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

namespace Tuleap\Tracker\Workflow\SimpleMode\State;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Transition;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollectionUpdater;
use Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdater;

class TransitionUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $condition_updater;
    private $collection_updater;

    /**
     * @var TransitionUpdater
     */
    private $transition_updater;

    protected function setUp(): void
    {
        parent::setUp();

        $this->condition_updater  = Mockery::mock(ConditionsUpdater::class);
        $this->collection_updater = Mockery::mock(PostActionCollectionUpdater::class);

        $this->transition_updater = new TransitionUpdater(
            $this->condition_updater,
            $this->collection_updater
        );
    }

    public function testUpdatesConditionsForAllTransitionsInState()
    {
        $transition    = Mockery::mock(Transition::class);
        $transition_02 = Mockery::mock(Transition::class);
        $transition_03 = Mockery::mock(Transition::class);

        $transition->shouldReceive('getIdFrom')->andReturn('');
        $transition_02->shouldReceive('getIdFrom')->andReturn('101');
        $transition_03->shouldReceive('getIdFrom')->andReturn('101');

        $transition->shouldReceive('getIdTo')->andReturn('101');
        $transition_02->shouldReceive('getIdTo')->andReturn('102');
        $transition_03->shouldReceive('getIdTo')->andReturn('103');

        $state = new State(1, [$transition, $transition_02, $transition_03]);

        $this->condition_updater->shouldReceive('update')
            ->with(
                $transition,
                ['101_4'],
                [],
                false
            )
            ->once();

        $this->condition_updater->shouldReceive('update')
            ->with(
                $transition_02,
                ['101_4'],
                [],
                false
            )
            ->once();

        $this->condition_updater->shouldReceive('update')
            ->with(
                $transition_03,
                ['101_4'],
                [],
                false
            )
            ->once();

        $this->transition_updater->updateStatePreConditions(
            $state,
            ['101_4'],
            [],
            false
        );
    }

    public function testUpdatesTheUniqueTranstionFromState()
    {
        $transition = Mockery::mock(Transition::class);
        $transition->shouldReceive('getIdFrom')->andReturn('');
        $transition->shouldReceive('getIdTo')->andReturn('101');

        $state = new State(1, [$transition]);

        $this->condition_updater->shouldReceive('update')
            ->with(
                $transition,
                ['101_4'],
                [],
                false
            )
            ->once();

        $this->transition_updater->updateStatePreConditions(
            $state,
            ['101_4'],
            [],
            false
        );
    }

    public function testUpdatesThePostActionsForState()
    {
        $transition    = Mockery::mock(Transition::class);
        $transition_02 = Mockery::mock(Transition::class);
        $transition_03 = Mockery::mock(Transition::class);

        $state = new State(1, [$transition, $transition_02, $transition_03]);

        $post_actions = new PostActionCollection();

        $this->collection_updater->shouldReceive('updateByTransition')
            ->with(
                $transition,
                $post_actions
            )
            ->once();

        $this->collection_updater->shouldReceive('updateByTransition')
            ->with(
                $transition_02,
                $post_actions
            )
            ->once();

        $this->collection_updater->shouldReceive('updateByTransition')
            ->with(
                $transition_03,
                $post_actions
            )
            ->once();

        $this->transition_updater->updateStateActions(
            $state,
            $post_actions
        );
    }
}
