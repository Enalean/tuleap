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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;
use Tuleap\Tracker\Workflow\PostAction\Update\TransitionFactory;

class CIBuildUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CIBuildUpdater
     */
    private $updater;
    /**
     *
     * @var MockInterface
     */
    private $ci_build_repository;

    /**
     * @var CIBuildValidator | MockInterface
     */
    private $validator;

    /**
     * @before
     */
    public function createUpdater()
    {
        $this->ci_build_repository = Mockery::mock(CIBuildRepository::class);
        $this->ci_build_repository
            ->shouldReceive('deleteAllByTransitionIfIdNotIn')
            ->byDefault();
        $this->ci_build_repository
            ->shouldReceive('update')
            ->byDefault();

        $this->validator = Mockery::mock(CIBuildValidator::class);
        $this->updater   = new CIBuildUpdater($this->ci_build_repository, $this->validator);
    }

    public function testUpdateAddsNewCIBuildActions()
    {
        $transition = TransitionFactory::buildATransition();
        $this->mockFindAllIdsByTransition($transition, [1]);

        $added_action = new CIBuild(null, 'http://example.test');
        $actions      = new PostActionCollection($added_action);

        $this->validator
            ->shouldReceive('validate')
            ->withArgs([$actions]);

        $this->ci_build_repository
            ->shouldReceive('create')
            ->with($transition, $added_action)
            ->andReturns();

        $this->updater->updateByTransition($actions, $transition);
    }

    /**
     * @expectedException \Tuleap\Tracker\Workflow\PostAction\Update\Internal\DuplicateCIBuildPostAction
     */
    public function testUpdateDoesNothingIfActionsAreNotValid()
    {
        $transition = TransitionFactory::buildATransition();
        $this->mockFindAllIdsByTransition($transition, [1]);

        $action  = new CIBuild(1, 'invalid action');
        $actions = new PostActionCollection($action);

        $this->validator
            ->shouldReceive('validate')
            ->withArgs([$actions])
            ->andThrow(new DuplicateCIBuildPostAction());

        $this->ci_build_repository->shouldNotReceive('deleteAllByTransitionIfIdNotIn', 'create', 'update');

        $this->updater->updateByTransition($actions, $transition);
    }

    public function testUpdateUpdatesCIBuildActionsWhichAlreadyExists()
    {
        $transition = TransitionFactory::buildATransition();
        $this->mockFindAllIdsByTransition($transition, [1]);

        $updated_action = new CIBuild(1, 'http://example.test');
        $actions        = new PostActionCollection($updated_action);

        $this->validator
            ->shouldReceive('validate')
            ->withArgs([$actions]);

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

        $action  = new CIBuild(2, 'http://example.test');
        $actions = new PostActionCollection($action);

        $this->validator
            ->shouldReceive('validate')
            ->withArgs([$actions]);

        $this->ci_build_repository
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
        $this->ci_build_repository
            ->shouldReceive('findAllIdsByTransition')
            ->withArgs([$transition])
            ->andReturn($existing_ids);
    }
}
