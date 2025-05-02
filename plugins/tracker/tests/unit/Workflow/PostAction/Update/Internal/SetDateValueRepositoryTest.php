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
use Transition_PostAction_Field_DateDao;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Workflow\PostAction\Update\SetDateValue;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SetDateValueRepositoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SetDateValueRepository $set_date_value_repository;

    private Transition_PostAction_Field_DateDao&MockObject $set_date_value_dao;

    #[Before]
    public function createRepository(): void
    {
        $this->set_date_value_dao = $this->createMock(Transition_PostAction_Field_DateDao::class);

        $this->set_date_value_repository = new SetDateValueRepository(
            $this->set_date_value_dao,
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testCreateCreatesGivenSetDateValueOnGivenTransition(): void
    {
        $this->set_date_value_dao->expects($this->atLeast(1))->method('create')
            ->with(1)
            ->willReturn(9);
        $this->set_date_value_dao->expects($this->atLeast(1))->method('updatePostAction')
            ->with(9, 43, 1);

        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);
        $set_date_value = new SetDateValue(43, 1);

        $this->set_date_value_repository->create($transition, $set_date_value);
    }

    public function testCreateThrowsWhenCreationFail(): void
    {
        $this->set_date_value_dao->method('create')
            ->willReturn(false);

        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);
        $set_date_value = new SetDateValue(43, 1);

        $this->expectException(DataAccessQueryException::class);

        $this->set_date_value_repository->create($transition, $set_date_value);
    }

    public function testDeleteDeletesByTransitionId(): void
    {
        $this->set_date_value_dao->expects($this->atLeast(1))->method('deletePostActionsByTransitionId')
            ->with(1)
            ->willReturn(true);

        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);

        $this->set_date_value_repository->deleteAllByTransition($transition);
    }

    public function testDeleteThrowsWhenDeletionFails(): void
    {
        $this->set_date_value_dao->method('deletePostActionsByTransitionId')
            ->with(1)
            ->willReturn(false);

        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);

        $this->expectException(DataAccessQueryException::class);

        $this->set_date_value_repository->deleteAllByTransition($transition);
    }
}
