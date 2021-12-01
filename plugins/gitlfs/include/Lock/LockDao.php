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

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

class LockDao extends DataAccessObject
{
    public function create(
        string $lock_path,
        int $lock_owner,
        ?string $reference,
        int $repository_id,
        int $creation_date,
    ): int {
        return (int) $this->getDB()->insertReturnId(
            'plugin_gitlfs_lock',
            [
                'lock_path'     => $lock_path,
                'lock_owner'    => $lock_owner,
                'ref'           => $reference,
                'repository_id' => $repository_id,
                'creation_date' => $creation_date,
            ]
        );
    }

    public function searchLocks(
        ?int $id,
        ?string $path,
        ?string $ref,
        ?int $owner,
        int $repository,
    ): array {
        $condition = $this->buildSearchCondition($id, $path, $ref, $owner, $repository);

        return $this->getDB()->safeQuery(
            "SELECT *
            FROM plugin_gitlfs_lock
            WHERE $condition",
            $condition->values()
        );
    }

    public function searchLocksNotBelongingToOwner(?string $ref, int $owner, int $repository): array
    {
        $condition = EasyStatement::open()
            ->with('lock_owner <> ?', $owner)
            ->andWith('repository_id = ?', $repository);

        if ($ref !== null) {
            $condition->andWith('ref = ?', $ref);
        }

        return $this->getDB()->safeQuery(
            "SELECT *
            FROM plugin_gitlfs_lock
            WHERE $condition",
            $condition->values()
        );
    }

    public function deleteLock(int $lock_id): void
    {
        $this->getDB()->delete(
            'plugin_gitlfs_lock',
            ['id' => $lock_id]
        );
    }

    private function buildSearchCondition(
        ?int $id,
        ?string $path,
        ?string $ref,
        ?int $owner,
        int $repository,
    ): EasyStatement {
        $condition = EasyStatement::open();

        if ($id !== null) {
            $condition = $condition->andWith('id = ?', $id);
        }

        if ($path !== null) {
            $condition = $condition->andWith('lock_path = ?', $path);
        }

        if ($ref !== null) {
            $condition = $condition->andWith('ref = ?', $ref);
        }

        if ($owner !== null) {
            $condition = $condition->andWith('lock_owner = ?', $owner);
        }

        $condition = $condition->andWith('repository_id = ?', $repository);

        return $condition;
    }
}
