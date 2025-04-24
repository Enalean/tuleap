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
use PHPUnit\Framework\MockObject\MockObject;
use Transition;
use Transition_PostAction_Field_IntDao;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Workflow\PostAction\Update\SetIntValue;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SetIntValueRepositoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SetIntValueRepository $set_int_value_repository;

    private Transition_PostAction_Field_IntDao&MockObject $set_int_value_dao;

    #[\PHPUnit\Framework\Attributes\Before]
    public function createRepository(): void
    {
        $this->set_int_value_dao = $this->createMock(Transition_PostAction_Field_IntDao::class);

        $this->set_int_value_repository = new SetIntValueRepository(
            $this->set_int_value_dao,
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testCreateCreatesGivenSetIntValueOnGivenTransition(): void
    {
        $this->set_int_value_dao->expects($this->atLeast(1))->method('create')
            ->with(1)
            ->willReturn(9);
        $this->set_int_value_dao->expects($this->atLeast(1))->method('updatePostAction')
            ->with(9, 43, 1);

        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);
        $set_int_value = new SetIntValue(43, 1);

        $this->set_int_value_repository->create($transition, $set_int_value);
    }

    public function testCreateThrowsWhenCreationFail(): void
    {
        $this->set_int_value_dao->method('create')
            ->willReturn(false);

        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);
        $set_int_value = new SetIntValue(43, 1);

        $this->expectException(DataAccessQueryException::class);

        $this->set_int_value_repository->create($transition, $set_int_value);
    }

    public function testDeleteAllByTransitionIfNotInDeletesExpectedTransitions(): void
    {
        $this->set_int_value_dao->expects($this->atLeast(1))
            ->method('deletePostActionsByTransitionId')
            ->with(1)
            ->willReturn(true);
        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);
        $this->set_int_value_repository->deleteAllByTransition($transition);
    }

    public function testDeleteAllByTransitionIfNotInThrowsIfDeleteFail(): void
    {
        $this->set_int_value_dao
            ->method('deletePostActionsByTransitionId')
            ->willReturn(false);
        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);

        $this->expectException(DataAccessQueryException::class);

        $this->set_int_value_repository->deleteAllByTransition($transition);
    }
}
