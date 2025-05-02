<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use DataAccessQueryException;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\MockObject\MockObject;
use Transition;
use Transition_PostAction_CIBuildDao;
use Tuleap\Tracker\Workflow\PostAction\Update\CIBuildValue;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CIBuildRepositoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CIBuildValueRepository $ci_build_repository;

    private Transition_PostAction_CIBuildDao&MockObject $ci_build_dao;

    #[Before]
    public function createRepository(): void
    {
        $this->ci_build_dao        = $this->createMock(Transition_PostAction_CIBuildDao::class);
        $this->ci_build_repository = new CIBuildValueRepository($this->ci_build_dao);
    }

    public function testCreateCreatesGivenCIBuildOnGivenTransition(): void
    {
        $this->ci_build_dao->expects($this->atLeast(1))->method('create')
            ->with(1, 'http://added-ci-url.test')
            ->willReturn(9);

        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);
        $ci_build = new CIBuildValue('http://added-ci-url.test');

        $this->ci_build_repository->create($transition, $ci_build);
    }

    public function testCreateThrowsWhenCreationFail(): void
    {
        $this->ci_build_dao->method('create')
            ->willReturn(false);

        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);
        $ci_build = new CIBuildValue('http://example.test');

        $this->expectException(DataAccessQueryException::class);

        $this->ci_build_repository->create($transition, $ci_build);
    }

    public function testDeleteAllByTransitionDeletesExpectedTransitions(): void
    {
        $this->ci_build_dao->expects($this->atLeast(1))
            ->method('deletePostActionByTransition')
            ->with(1)
            ->willReturn(true);

        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);
        $this->ci_build_repository->deleteAllByTransition($transition);
    }

    public function testDeleteAllByTransitionIfNotInThrowsIfDeleteFail(): void
    {
        $this->ci_build_dao
            ->method('deletePostActionByTransition')
            ->willReturn(false);
        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);

        $this->expectException(DataAccessQueryException::class);

        $this->ci_build_repository->deleteAllByTransition($transition);
    }
}
