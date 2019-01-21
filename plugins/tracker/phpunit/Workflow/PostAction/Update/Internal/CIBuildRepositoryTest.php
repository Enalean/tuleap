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

use DataAccessQueryException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Transition;
use Transition_PostAction_CIBuildDao;

class CIBuildRepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CIBuildRepository
     */
    private $ci_build_repository;

    /**
     * @var MockInterface
     */
    private $ci_build_dao;

    /**
     * @before
     */
    public function createRepository()
    {
        $this->ci_build_dao        = Mockery::mock(Transition_PostAction_CIBuildDao::class);
        $this->ci_build_repository = new CIBuildRepository($this->ci_build_dao);
    }

    public function testCreateCreatesGivenCIBuildOnGivenTransition()
    {
        $this->ci_build_dao->shouldReceive('create')
            ->with(1, 'http://added-ci-url.test')
            ->andReturn(9);

        $transition = $this->buildATransitionWithId(1);
        $ci_build   = new CIBuild(9, 'http://added-ci-url.test');

        $this->ci_build_repository->create($transition, $ci_build);
    }

    /**
     * @expectedException DataAccessQueryException
     */
    public function testCreateThrowsWhenCreationFail()
    {
        $this->ci_build_dao->shouldReceive('create')
            ->andReturn(false);

        $transition = $this->buildATransition();
        $ci_build   = new CIBuild(null, 'http://example.test');

        $this->ci_build_repository->create($transition, $ci_build);
    }

    public function testUpdateUpdatesGivenCIBuild()
    {
        $this->ci_build_dao
            ->shouldReceive('updatePostAction')
            ->with(9, 'http://updated-ci-url.test')
            ->andReturn(true);
        $ci_build = new CIBuild(9, 'http://updated-ci-url.test');
        $this->ci_build_repository->update($ci_build);
    }

    /**
     * @expectedException DataAccessQueryException
     */
    public function testUpdateThrowsWhenUpdateFail()
    {
        $this->ci_build_dao
            ->shouldReceive('updatePostAction')
            ->andReturn(false);
        $ci_build = new CIBuild(9, 'http://updated-ci-url.test');
        $this->ci_build_repository->update($ci_build);
    }

    public function testDeleteAllByTransitionIfIdNotInDeletesExpectedTransitions()
    {
        $this->ci_build_dao
            ->shouldReceive('deletePostActionByTransitionIfIdNotIn')
            ->with(1, [1, 2, 3])
            ->andReturn(true);
        $transition = $this->buildATransitionWithId(1);
        $this->ci_build_repository->deleteAllByTransitionIfIdNotIn($transition, [1, 2, 3]);
    }

    /**
     * @expectedException DataAccessQueryException
     */
    public function testDeleteAllByTransitionIfIdNotInThrowsIfDeleteFail()
    {
        $this->ci_build_dao
            ->shouldReceive('deletePostActionByTransitionIfIdNotIn')
            ->andReturn(false);
        $transition = $this->buildATransition();
        $this->ci_build_repository->deleteAllByTransitionIfIdNotIn($transition, [1, 2, 3]);
    }

    public function testFindAllIdsByTransitionReturnsIdsOfAllActionsOnGivenTransition()
    {
        $this->ci_build_dao
            ->shouldReceive('findAllIdsByTransitionId')
            ->with(1)
            ->andReturn([
                ['id' => 1],
                ['id' => 2],
                ['id' => 3]
            ]);

        $transition = $this->buildATransitionWithId(1);
        $ids        = $this->ci_build_repository->findAllIdsByTransition($transition);

        $this->assertEquals([1, 2, 3], $ids);
    }

    /**
     * @expectedException DataAccessQueryException
     */
    public function testFindAllIdsByTransitionThrowsWhenFindFail()
    {
        $this->ci_build_dao
            ->shouldReceive('findAllIdsByTransitionId')
            ->andReturn(false);
        $transition = $this->buildATransition();
        $this->ci_build_repository->findAllIdsByTransition($transition);
    }

    private function buildATransitionWithId(int $id): MockInterface
    {
        $transition = $this->buildATransition();
        $transition->shouldReceive('getId')
            ->andReturn($id);
        return $transition;
    }

    private function buildATransition(): MockInterface
    {
        $transition = Mockery::mock(Transition::class);
        $transition->shouldReceive('getId')
            ->andReturn(1)
            ->byDefault();
        return $transition;
    }
}
