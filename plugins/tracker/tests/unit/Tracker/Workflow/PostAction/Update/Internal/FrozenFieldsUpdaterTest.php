<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

require_once(__DIR__ . '/../TransitionFactory.php');

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tuleap\Tracker\Workflow\PostAction\Update\FrozenFieldsValue;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;
use Tuleap\Tracker\Workflow\PostAction\Update\TransitionFactory;

class FrozenFieldsUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var FrozenFieldsValueUpdater
     */
    private $updater;
    /**
     *
     * @var MockInterface
     */
    private $frozen_fields_repository;
    /**
     *
     * @var MockInterface
     */
    private $frozen_fields_validator;

    /**
     * @before
     */
    public function createUpdater()
    {
        $this->frozen_fields_repository = Mockery::mock(FrozenFieldsValueRepository::class);
        $this->frozen_fields_repository
            ->shouldReceive('deleteAllByTransition')
            ->byDefault();
        $this->frozen_fields_repository
            ->shouldReceive('create')
            ->byDefault();

        $this->frozen_fields_validator = Mockery::mock(FrozenFieldsValueValidator::class);

        $this->updater = new FrozenFieldsValueUpdater($this->frozen_fields_repository, $this->frozen_fields_validator);
    }

    public function testUpdateAddsNewFrozenFieldsActions()
    {
        $transition   = TransitionFactory::buildATransitionWithTracker(Mockery::mock(Tracker::class));
        $added_action = new FrozenFieldsValue([]);
        $actions      = new PostActionCollection($added_action);

        $this->frozen_fields_validator->shouldReceive('validate')->once();

        $this->frozen_fields_repository
            ->shouldReceive('create')
            ->with($transition, $added_action)
            ->andReturns();

        $this->updater->updateByTransition($actions, $transition);
    }

    public function testUpdateDeletesAllPreExistingFrozenFieldsActions()
    {
        $transition     = TransitionFactory::buildATransitionWithTracker(Mockery::mock(Tracker::class));
        $updated_action = new FrozenFieldsValue([]);
        $actions        = new PostActionCollection($updated_action);

        $this->frozen_fields_validator->shouldReceive('validate')->once();

        $this->frozen_fields_repository
            ->shouldReceive('deleteAllByTransition')
            ->with($updated_action)
            ->andReturns();

        $this->updater->updateByTransition($actions, $transition);
    }

    public function testItDoesNothingIfFrozenFieldsActionsAreNotValid()
    {
        $transition     = TransitionFactory::buildATransitionWithTracker(Mockery::mock(Tracker::class));
        $updated_action = new FrozenFieldsValue([]);
        $actions        = new PostActionCollection($updated_action);

        $this->frozen_fields_validator->shouldReceive('validate')->andThrow(InvalidPostActionException::class);

        $this->frozen_fields_repository->shouldReceive('deleteAllByTransition')->never();
        $this->frozen_fields_repository->shouldReceive('create')->never();

        $this->expectException(InvalidPostActionException::class);

        $this->updater->updateByTransition($actions, $transition);
    }
}
