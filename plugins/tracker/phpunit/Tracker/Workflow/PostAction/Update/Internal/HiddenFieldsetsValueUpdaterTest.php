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
use Tuleap\Tracker\Workflow\PostAction\Update\HiddenFieldsetsValue;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;
use Tuleap\Tracker\Workflow\PostAction\Update\TransitionFactory;

class HiddenFieldsetsValueUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var HiddenFieldsetsValueUpdater
     */
    private $updater;
    /**
     *
     * @var MockInterface
     */
    private $hidden_fieldsets_value_repository;
    /**
     *
     * @var MockInterface
     */
    private $hidden_fieldsets_value_validator;

    /**
     * @before
     */
    public function createUpdater()
    {
        $this->hidden_fieldsets_value_repository = Mockery::mock(HiddenFieldsetsValueRepository::class);
        $this->hidden_fieldsets_value_repository
            ->shouldReceive('deleteAllByTransition')
            ->byDefault();
        $this->hidden_fieldsets_value_repository
            ->shouldReceive('create')
            ->byDefault();

        $this->hidden_fieldsets_value_validator = Mockery::mock(HiddenFieldsetsValueValidator::class);

        $this->updater = new HiddenFieldsetsValueUpdater(
            $this->hidden_fieldsets_value_repository,
            $this->hidden_fieldsets_value_validator
        );
    }

    public function testUpdateAddsNewHiddenFieldsetsActions()
    {
        $transition   = TransitionFactory::buildATransitionWithTracker(Mockery::mock(Tracker::class));
        $added_action = new HiddenFieldsetsValue([]);
        $actions      = new PostActionCollection($added_action);

        $this->hidden_fieldsets_value_validator->shouldReceive('validate')->once();

        $this->hidden_fieldsets_value_repository
            ->shouldReceive('create')
            ->with($transition, $added_action)
            ->andReturns();

        $this->updater->updateByTransition($actions, $transition);
    }

    public function testUpdateDeletesAllPreExistingHiddenFieldsetsActions()
    {
        $transition     = TransitionFactory::buildATransitionWithTracker(Mockery::mock(Tracker::class));
        $updated_action = new HiddenFieldsetsValue([]);
        $actions        = new PostActionCollection($updated_action);

        $this->hidden_fieldsets_value_validator->shouldReceive('validate')->once();

        $this->hidden_fieldsets_value_repository
            ->shouldReceive('deleteAllByTransition')
            ->with($updated_action)
            ->andReturns();

        $this->updater->updateByTransition($actions, $transition);
    }

    public function testItDoesNothingIfHiddenFieldsetsActionsAreNotValid()
    {
        $transition     = TransitionFactory::buildATransitionWithTracker(Mockery::mock(Tracker::class));
        $updated_action = new HiddenFieldsetsValue([]);
        $actions        = new PostActionCollection($updated_action);

        $this->hidden_fieldsets_value_validator->shouldReceive('validate')->andThrow(InvalidPostActionException::class);

        $this->hidden_fieldsets_value_repository->shouldReceive('deleteAllByTransition')->never();
        $this->hidden_fieldsets_value_repository->shouldReceive('create')->never();

        $this->expectException(InvalidPostActionException::class);

        $this->updater->updateByTransition($actions, $transition);
    }
}
