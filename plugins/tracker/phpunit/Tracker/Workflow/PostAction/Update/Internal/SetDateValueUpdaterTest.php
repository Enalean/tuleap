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

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

require_once(__DIR__ . '/../TransitionFactory.php');

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;
use Tuleap\Tracker\Workflow\PostAction\Update\TransitionFactory;

class SetDateValueUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var SetDateValueUpdater
     */
    private $updater;
    /**
     *
     * @var MockInterface
     */
    private $set_date_value_repository;
    /**
     * @var MockInterface
     */
    private $validator;
    /**
     * @var MockInterface
     */
    private $tracker;

    /**
     * @before
     */
    public function createUpdater()
    {
        $this->set_date_value_repository = Mockery::mock(SetDateValueRepository::class);
        $this->set_date_value_repository
            ->shouldReceive('deleteAllByTransitionIfIdNotIn')
            ->byDefault();
        $this->set_date_value_repository
            ->shouldReceive('update')
            ->byDefault();
        $this->tracker = Mockery::mock(\Tracker::class);

        $this->validator = Mockery::mock(SetDateValueValidator::class);
        $this->updater = new SetDateValueUpdater($this->set_date_value_repository, $this->validator);
    }

    public function testUpdateAddsNewSetDateValueActions()
    {
        $transition = $this->mockTransitionWithTracker();
        $this->mockFindAllIdsByTransition($transition, [1]);

        $added_action = new SetDateValue(null, 43, 1);
        $actions      = new PostActionCollection($added_action);

        $this->validator
            ->shouldReceive('validate')
            ->with($this->tracker, $added_action);

        $this->set_date_value_repository
            ->shouldReceive('create')
            ->with($transition, $added_action)
            ->andReturns();

        $this->updater->updateByTransition($actions, $transition);
    }

    public function testUpdateUpdatesSetDateValueActionsWhichAlreadyExists()
    {
        $transition = $this->mockTransitionWithTracker();
        $this->mockFindAllIdsByTransition($transition, [1]);

        $updated_action = new SetDateValue(1, 43, 1);
        $actions        = new PostActionCollection($updated_action);

        $this->validator
            ->shouldReceive('validate')
            ->with($this->tracker, $updated_action);

        $this->set_date_value_repository
            ->shouldReceive('update')
            ->with($updated_action)
            ->andReturns();

        $this->updater->updateByTransition($actions, $transition);
    }

    public function testUpdateDeletesRemovedSetDateValueActions()
    {
        $transition = $this->mockTransitionWithTracker();
        $this->mockFindAllIdsByTransition($transition, [2, 3]);

        $action  = new SetDateValue(2, 43, 1);
        $actions = new PostActionCollection($action);

        $this->validator
            ->shouldReceive('validate')
            ->with($this->tracker, $action);

        $this->set_date_value_repository
            ->shouldReceive('deleteAllByTransitionIfIdNotIn')
            ->with($transition, [2])
            ->andReturns();

        $this->updater->updateByTransition($actions, $transition);
    }

    private function mockFindAllIdsByTransition(
        $transition,
        array $ids
    ) {
        $existing_ids = new PostActionIdCollection(...$ids);
        $this->set_date_value_repository
            ->shouldReceive('findAllIdsByTransition')
            ->withArgs([$transition])
            ->andReturn($existing_ids);
    }

    private function mockTransitionWithTracker()
    {
        $transition    = TransitionFactory::buildATransition();
        $workflow      = Mockery::mock(\Workflow::class);
        $workflow->shouldReceive('getTracker')
            ->andReturn($this->tracker);
        $transition->shouldReceive('getWorkflow')
            ->andReturn($workflow);
        return $transition;
    }
}
