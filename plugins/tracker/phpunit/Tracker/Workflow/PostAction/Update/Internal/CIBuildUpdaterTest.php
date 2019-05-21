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
use Tuleap\Tracker\Workflow\PostAction\Update\CIBuildValue;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;
use Tuleap\Tracker\Workflow\PostAction\Update\TransitionFactory;

class CIBuildUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CIBuildValueUpdater
     */
    private $updater;
    /**
     *
     * @var MockInterface
     */
    private $ci_build_repository;

    /**
     * @var CIBuildValueValidator | MockInterface
     */
    private $validator;

    /**
     * @before
     */
    public function createUpdater()
    {
        $this->ci_build_repository = Mockery::mock(CIBuildValueRepository::class);
        $this->ci_build_repository
            ->shouldReceive('deleteAllByTransitionIfNotIn')
            ->byDefault();
        $this->ci_build_repository
            ->shouldReceive('update')
            ->byDefault();

        $this->validator = Mockery::mock(CIBuildValueValidator::class);
        $this->updater   = new CIBuildValueUpdater($this->ci_build_repository, $this->validator);
    }

    public function testUpdateAddsNewCIBuildActions()
    {
        $transition = TransitionFactory::buildATransition();
        $this->mockFindAllIdsByTransition($transition, [1]);

        $added_action = new CIBuildValue(null, 'http://example.test');
        $actions      = new PostActionCollection($added_action);

        $this->validator
            ->shouldReceive('validate')
            ->with($added_action);

        $this->ci_build_repository
            ->shouldReceive('create')
            ->with($transition, $added_action)
            ->andReturns();

        $this->updater->updateByTransition($actions, $transition);
    }

    public function testUpdateUpdatesCIBuildActionsWhichAlreadyExists()
    {
        $transition = TransitionFactory::buildATransition();
        $this->mockFindAllIdsByTransition($transition, [1]);

        $updated_action = new CIBuildValue(1, 'http://example.test');
        $actions        = new PostActionCollection($updated_action);

        $this->validator
            ->shouldReceive('validate')
            ->with($updated_action);

        $this->ci_build_repository
            ->shouldReceive('update')
            ->with($updated_action)
            ->andReturns();

        $this->updater->updateByTransition($actions, $transition);
    }

    public function testUpdateDeletesRemovedCIBuildActions()
    {
        $transition = TransitionFactory::buildATransition();

        $this->mockFindAllIdsByTransition($transition, [2, 3]);

        $action  = new CIBuildValue(2, 'http://example.test');
        $actions = new PostActionCollection($action);

        $this->validator
            ->shouldReceive('validate')
            ->with($action);

        $this->ci_build_repository
            ->shouldReceive('deleteAllByTransitionIfNotIn')
            ->with($transition, [$action])
            ->andReturns();

        $this->updater->updateByTransition($actions, $transition);
    }

    private function mockFindAllIdsByTransition(
        $transition,
        array $ids
    ) {
        $existing_ids = new PostActionIdCollection(...$ids);
        $this->ci_build_repository
            ->shouldReceive('findAllIdsByTransition')
            ->withArgs([$transition])
            ->andReturn($existing_ids);
    }
}
