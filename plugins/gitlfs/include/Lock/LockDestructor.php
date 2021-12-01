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

namespace Tuleap\GitLFS\Lock;

use PFUser;

class LockDestructor
{
    /**
     * @var LockDao
     */
    private $lock_dao;

    public function __construct(LockDao $lock_dao)
    {
        $this->lock_dao = $lock_dao;
    }

    /**
     * @throws LockDestructionNotAuthorizedException
     */
    public function deleteLock(
        Lock $lock,
        PFUser $user,
    ): void {
        if (! $this->userIsOwner($user, $lock)) {
            throw new LockDestructionNotAuthorizedException();
        }

        $this->lock_dao->deleteLock($lock->getId());
    }

    public function forceDeleteLock(
        Lock $lock,
    ): void {
        $this->lock_dao->deleteLock($lock->getId());
    }

    private function userIsOwner(PFUser $user, Lock $lock): bool
    {
        return $lock->getOwner()->getId() === $user->getId();
    }
}
