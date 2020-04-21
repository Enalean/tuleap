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
 */

namespace Tuleap\GitLFS\Lock;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class LockDestructorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var LockDestructor
     */
    private $lock_destructor;

    /**
     * @var MockInterface
     */
    private $lock_dao;

    public function setUp(): void
    {
        $this->lock_dao        = \Mockery::mock(LockDao::class);
        $this->lock_destructor = new LockDestructor($this->lock_dao);
    }

    public function testLockIsNotDeletedIfUserIsNotOwner()
    {
        $lock_owner   = \Mockery::mock(\PFUser::class);
        $request_user = \Mockery::mock(\PFUser::class);

        $lock_owner->shouldReceive('getId')->andReturn(103);
        $request_user->shouldReceive('getId')->andReturn(104);

        $lock = new Lock(
            1,
            "",
            $lock_owner,
            1548080140
        );

        $this->lock_dao->shouldNotReceive('deleteLock');
        $this->expectException(LockDestructionNotAuthorizedException::class);

        $this->lock_destructor->deleteLock($lock, $request_user);
    }

    public function testLockIsDeletedIfUserIsOwner()
    {
        $lock_owner   = \Mockery::mock(\PFUser::class);

        $lock_owner->shouldReceive('getId')->andReturn(103);

        $lock = new Lock(
            1,
            "",
            $lock_owner,
            1548080140
        );

        $this->lock_dao->shouldReceive('deleteLock')->with($lock->getId());

        $this->lock_destructor->deleteLock($lock, $lock_owner);
    }

    public function testLockIsDeletedEvenIfUserIsNotOwnerIfForced()
    {
        $lock_owner   = \Mockery::mock(\PFUser::class);

        $lock_owner->shouldReceive('getId')->andReturn(103);

        $lock = new Lock(
            1,
            "",
            $lock_owner,
            1548080140
        );

        $this->lock_dao->shouldReceive('deleteLock')->with($lock->getId());

        $this->lock_destructor->forceDeleteLock($lock);
    }
}
