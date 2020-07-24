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

use GitRepository;
use PFUser;
use UserManager;

class LockRetriever
{
    /**
     * @var LockDao
     */
    private $lock_dao;

    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(
        LockDao $lock_dao,
        UserManager $user_manager
    ) {
        $this->lock_dao     = $lock_dao;
        $this->user_manager = $user_manager;
    }

    public function retrieveLocks(
        ?int $id,
        ?string $path,
        ?string $ref,
        ?PFUser $owner,
        GitRepository $repository
    ): array {
        $lock_rows = $this->lock_dao->searchLocks(
            $id,
            $path,
            $ref,
            $owner ? $owner->getId() : null,
            $repository->getId()
        );

        return $this->instantiateLocksFromRows($lock_rows);
    }

    public function retrieveLocksNotBelongingToOwner(?string $ref, PFUser $owner, GitRepository $repository): array
    {
        return $this->instantiateLocksFromRows(
            $this->lock_dao->searchLocksNotBelongingToOwner($ref, $owner->getId(), $repository->getId())
        );
    }

    private function instantiateLocksFromRows(array $lock_rows): array
    {
        $locks = [];

        foreach ($lock_rows as $lock_row) {
            $locks[] = $this->instantiateLockFromRow($lock_row);
        }

        return $locks;
    }

    private function instantiateLockFromRow(array $lock_row): Lock
    {
        return new Lock(
            $lock_row['id'],
            $lock_row['lock_path'],
            $this->user_manager->getUserById($lock_row['lock_owner']),
            $lock_row['creation_date']
        );
    }
}
