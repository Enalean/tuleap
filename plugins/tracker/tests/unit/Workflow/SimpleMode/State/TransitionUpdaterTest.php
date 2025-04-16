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

use PHPUnit\Framework\MockObject\MockObject;
use Transition;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollectionUpdater;
use Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdater;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TransitionUpdaterTest extends TestCase
{
    private ConditionsUpdater&MockObject $condition_updater;
    private PostActionCollectionUpdater&MockObject $collection_updater;

    private TransitionUpdater $transition_updater;

    protected function setUp(): void
    {
        parent::setUp();

        $this->condition_updater  = $this->createMock(ConditionsUpdater::class);
        $this->collection_updater = $this->createMock(PostActionCollectionUpdater::class);

        $this->transition_updater = new TransitionUpdater(
            $this->condition_updater,
            $this->collection_updater
        );
    }

    public function testUpdatesConditionsForAllTransitionsInState()
    {
        $transition    = $this->createMock(Transition::class);
        $transition_02 = $this->createMock(Transition::class);
        $transition_03 = $this->createMock(Transition::class);

        $transition->method('getIdFrom')->willReturn('');
        $transition_02->method('getIdFrom')->willReturn('101');
        $transition_03->method('getIdFrom')->willReturn('101');

        $transition->method('getIdTo')->willReturn('101');
        $transition_02->method('getIdTo')->willReturn('102');
        $transition_03->method('getIdTo')->willReturn('103');

        $state = new State(1, [$transition, $transition_02, $transition_03]);

        $this->condition_updater
            ->expects($this->exactly(3))
            ->method('update')
            ->willReturnCallback(
                static fn (
                    Transition $arg_transition,
                ) => match ($arg_transition) {
                    $transition,
                    $transition_02,
                    $transition_03 => true
                }
            );

        $this->transition_updater->updateStatePreConditions(
            $state,
            ['101_4'],
            [],
            false
        );
    }

    public function testUpdatesTheUniqueTranstionFromState()
    {
        $transition = $this->createMock(Transition::class);
        $transition->method('getIdFrom')->willReturn('');
        $transition->method('getIdTo')->willReturn('101');

        $state = new State(1, [$transition]);

        $this->condition_updater
            ->expects($this->once())
            ->method('update')
            ->with(
                $transition,
                ['101_4'],
                [],
                false
            );

        $this->transition_updater->updateStatePreConditions(
            $state,
            ['101_4'],
            [],
            false
        );
    }

    public function testUpdatesThePostActionsForState()
    {
        $transition    = $this->createMock(Transition::class);
        $transition_02 = $this->createMock(Transition::class);
        $transition_03 = $this->createMock(Transition::class);

        $state = new State(1, [$transition, $transition_02, $transition_03]);

        $post_actions = new PostActionCollection();

        $this->collection_updater
            ->expects($this->exactly(3))
            ->method('updateByTransition')
            ->willReturnCallback(
                static fn (
                    Transition $arg_transition,
                ) => match ($arg_transition) {
                    $transition,
                    $transition_02,
                    $transition_03 => true
                }
            );

        $this->transition_updater->updateStateActions(
            $state,
            $post_actions
        );
    }
}
