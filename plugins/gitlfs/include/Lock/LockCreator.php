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

use DateTimeImmutable;
use GitRepository;
use PFUser;

class LockCreator
{
    /**
     * @var LockDao
     */
    private $lock_dao;

    public function __construct(LockDao $lock_dao)
    {
        $this->lock_dao = $lock_dao;
    }

    public function createLock(
        string $path,
        PFUser $user,
        ?string $reference,
        GitRepository $repository,
        DateTimeImmutable $creation_time,
    ): Lock {
        $lock_id = $this->lock_dao->create(
            $path,
            $user->getId(),
            $reference,
            $repository->getId(),
            $creation_time->getTimestamp()
        );

        return new Lock(
            $lock_id,
            $path,
            $user,
            $creation_time->getTimestamp()
        );
    }
}
