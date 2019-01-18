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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Transition;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\CIBuild;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\CIBuildRepository;

class PostActionsUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PostActionsUpdater
     */
    private $post_action_updater;

    /**
     * @var MockInterface
     */
    private $ci_build_repository;

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

        $this->post_action_updater = new PostActionsUpdater($this->ci_build_repository);
    }

    public function testUpdateDeletesRemovedCIBuildActions()
    {
        $transition = $this->buildATransition();

        $this->ci_build_repository
            ->shouldReceive('findAllIdsByTransition')
            ->with($transition)
            ->andReturns([2, 3]);

        $action  = new CIBuild(1, 'http://example.test');
        $actions = new PostActionCollection([$action]);

        $this->ci_build_repository
            ->shouldReceive('deleteAllByTransitionIfIdNotIn')
            ->with($transition, [1])
            ->andReturns();

        $this->post_action_updater->updateByTransition($transition, $actions);
    }

    public function testUpdateAddsNewCIBuildActions()
    {
        $transition = $this->buildATransition();

        $this->ci_build_repository
            ->shouldReceive('findAllIdsByTransition')
            ->with($transition)
            ->andReturns([1]);

        $added_action = new CIBuild(1, 'http://example.test');
        $actions      = new PostActionCollection([$added_action]);

        $this->ci_build_repository
            ->shouldReceive('create')
            ->with($transition, $added_action)
            ->andReturns();

        $this->post_action_updater->updateByTransition($transition, $actions);
    }

    public function testUpdateUpdatesCIBuildActionsWhichAlreadyExists()
    {
        $transition = $this->buildATransition();

        $this->ci_build_repository
            ->shouldReceive('findAllIdsByTransition')
            ->with($transition)
            ->andReturns([1]);

        $updated_action = new CIBuild(1, 'http://example.test');
        $actions        = new PostActionCollection([$updated_action]);

        $this->ci_build_repository
            ->shouldReceive('update')
            ->with($updated_action)
            ->andReturns();

        $this->post_action_updater->updateByTransition($transition, $actions);
    }

    private function buildATransition(): MockInterface
    {
        return Mockery::mock(Transition::class);
    }
}
