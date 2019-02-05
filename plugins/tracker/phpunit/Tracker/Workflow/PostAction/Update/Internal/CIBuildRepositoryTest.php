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

require_once(__DIR__ . "/../TransitionFactory.php");

use DataAccessQueryException;
use FakeDataAccessResult;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Transition_PostAction_CIBuildDao;
use Tuleap\Tracker\Workflow\PostAction\Update\TransitionFactory;

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

        $transition = TransitionFactory::buildATransitionWithId(1);
        $ci_build   = new CIBuild(9, 'http://added-ci-url.test');

        $this->ci_build_repository->create($transition, $ci_build);
    }

    public function testCreateThrowsWhenCreationFail()
    {
        $this->ci_build_dao->shouldReceive('create')
            ->andReturn(false);

        $transition = TransitionFactory::buildATransition();
        $ci_build   = new CIBuild(null, 'http://example.test');

        $this->expectException(DataAccessQueryException::class);

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

    public function testUpdateThrowsWhenUpdateFail()
    {
        $this->ci_build_dao
            ->shouldReceive('updatePostAction')
            ->andReturn(false);
        $ci_build = new CIBuild(9, 'http://updated-ci-url.test');

        $this->expectException(DataAccessQueryException::class);

        $this->ci_build_repository->update($ci_build);
    }

    public function testDeleteAllByTransitionIfNotInDeletesExpectedTransitions()
    {
        $ci_builds = [
            new CIBuild(1, 'http://updated-ci-url-1.test'),
            new CIBuild(2, 'http://updated-ci-url-2.test'),
            new CIBuild(3, 'http://updated-ci-url-3.test')
        ];
        $this->ci_build_dao
            ->shouldReceive('deletePostActionByTransitionIfIdNotIn')
            ->with(1, [1, 2, 3])
            ->andReturn(true);
        $transition = TransitionFactory::buildATransitionWithId(1);
        $this->ci_build_repository->deleteAllByTransitionIfNotIn($transition, $ci_builds);
    }

    public function testDeleteAllByTransitionIfNotInThrowsIfDeleteFail()
    {
        $ci_builds = [
            new CIBuild(1, 'http://updated-ci-url-1.test'),
            new CIBuild(2, 'http://updated-ci-url-2.test'),
            new CIBuild(3, 'http://updated-ci-url-3.test')
        ];
        $this->ci_build_dao
            ->shouldReceive('deletePostActionByTransitionIfIdNotIn')
            ->andReturn(false);
        $transition = TransitionFactory::buildATransition();

        $this->expectException(DataAccessQueryException::class);

        $this->ci_build_repository->deleteAllByTransitionIfNotIn($transition, $ci_builds);
    }

    public function testFindAllIdsByTransitionReturnsIdsOfAllActionsOnGivenTransition()
    {
        $this->ci_build_dao
            ->shouldReceive('findAllIdsByTransitionId')
            ->with(1)
            ->andReturn(new FakeDataAccessResult([
                ['id' => 1],
                ['id' => 2],
                ['id' => 3]
            ]));

        $transition = TransitionFactory::buildATransitionWithId(1);
        $ids        = $this->ci_build_repository->findAllIdsByTransition($transition);

        $this->assertEquals(new PostActionIdCollection(1, 2, 3), $ids);
    }

    public function testFindAllIdsByTransitionThrowsWhenFindFail()
    {
        $this->ci_build_dao
            ->shouldReceive('findAllIdsByTransitionId')
            ->andReturn(false);
        $transition = TransitionFactory::buildATransition();

        $this->expectException(DataAccessQueryException::class);

        $this->ci_build_repository->findAllIdsByTransition($transition);
    }
}
