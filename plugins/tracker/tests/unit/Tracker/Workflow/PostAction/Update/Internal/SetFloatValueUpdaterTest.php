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
use Tuleap\Tracker\Workflow\PostAction\Update\SetFloatValue;
use Tuleap\Tracker\Workflow\PostAction\Update\TransitionFactory;

class SetFloatValueUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var SetFloatValueUpdater
     */
    private $updater;
    /**
     *
     * @var MockInterface
     */
    private $set_float_value_repository;

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
        $this->set_float_value_repository = Mockery::mock(SetFloatValueRepository::class);
        $this->set_float_value_repository
            ->shouldReceive('deleteAllByTransition')
            ->byDefault();

        $this->tracker = Mockery::mock(\Tracker::class);

        $this->validator = Mockery::mock(SetFloatValueValidator::class);

        $this->updater = new SetFloatValueUpdater($this->set_float_value_repository, $this->validator);
    }

    public function testUpdateAddsNewSetFloatValueActions()
    {
        $transition = TransitionFactory::buildATransitionWithTracker($this->tracker);

        $added_action = new SetFloatValue(43, 1.23);
        $actions      = new PostActionCollection($added_action);

        $this->validator
            ->shouldReceive('validate')
            ->with($this->tracker, $added_action);

        $this->set_float_value_repository
            ->shouldReceive('create')
            ->with($transition, $added_action)
            ->andReturns();

        $this->updater->updateByTransition($actions, $transition);
    }

    public function testUpdateDeletesAndRecreatesSetFloatValueActionsWhichAlreadyExists()
    {
        $transition = TransitionFactory::buildATransitionWithTracker($this->tracker);

        $updated_action = new SetFloatValue(43, 1.23);
        $actions        = new PostActionCollection($updated_action);

        $this->validator
            ->shouldReceive('validate')
            ->with($this->tracker, $updated_action);

        $this->set_float_value_repository
            ->shouldReceive('deleteAllByTransition')
            ->with($transition)
            ->andReturns();

        $this->set_float_value_repository
            ->shouldReceive('create')
            ->with($transition, $updated_action)
            ->andReturns();

        $this->updater->updateByTransition($actions, $transition);
    }

    public function testUpdateDeletesRemovedSetFloatValueActions()
    {
        $transition = TransitionFactory::buildATransitionWithTracker($this->tracker);

        $action  = new SetFloatValue(43, 1.23);
        $actions = new PostActionCollection($action);

        $this->validator
            ->shouldReceive('validate')
            ->with($this->tracker, $action);

        $this->set_float_value_repository
            ->shouldReceive('deleteAllByTransition')
            ->with($transition)
            ->andReturns();

        $this->set_float_value_repository
            ->shouldReceive('create')
            ->with($transition, $action)
            ->andReturns();

        $this->updater->updateByTransition($actions, $transition);
    }
}
