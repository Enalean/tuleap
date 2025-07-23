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
 */

declare(strict_types=1);

namespace Tuleap\GitLFS\Lock;


#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LockDestructorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private LockDestructor $lock_destructor;
    private LockDao&\PHPUnit\Framework\MockObject\MockObject $lock_dao;

    #[\Override]
    public function setUp(): void
    {
        $this->lock_dao        = $this->createMock(LockDao::class);
        $this->lock_destructor = new LockDestructor($this->lock_dao);
    }

    public function testLockIsNotDeletedIfUserIsNotOwner(): void
    {
        $lock_owner   = $this->createStub(\PFUser::class);
        $request_user = $this->createStub(\PFUser::class);

        $lock_owner->method('getId')->willReturn(103);
        $request_user->method('getId')->willReturn(104);

        $lock = new Lock(
            1,
            '',
            $lock_owner,
            1548080140
        );

        $this->lock_dao->expects($this->never())->method('deleteLock');
        $this->expectException(LockDestructionNotAuthorizedException::class);

        $this->lock_destructor->deleteLock($lock, $request_user);
    }

    public function testLockIsDeletedIfUserIsOwner(): void
    {
        $lock_owner = $this->createStub(\PFUser::class);

        $lock_owner->method('getId')->willReturn(103);

        $lock = new Lock(
            1,
            '',
            $lock_owner,
            1548080140
        );

        $this->lock_dao->expects($this->atLeastOnce())->method('deleteLock')->with($lock->getId());

        $this->lock_destructor->deleteLock($lock, $lock_owner);
    }

    public function testLockIsDeletedEvenIfUserIsNotOwnerIfForced(): void
    {
        $lock_owner = $this->createStub(\PFUser::class);

        $lock_owner->method('getId')->willReturn(103);

        $lock = new Lock(
            1,
            '',
            $lock_owner,
            1548080140
        );

        $this->lock_dao->expects($this->once())->method('deleteLock')->with($lock->getId());

        $this->lock_destructor->forceDeleteLock($lock);
    }
}
