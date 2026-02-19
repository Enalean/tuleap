<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\User\Avatar;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;
use Tuleap\Option\Option;

final class AvatarHashDao extends DataAccessObject implements AvatarHashStorage, AvatarHashStorageDeletor
{
    #[\Override]
    public function retrieve(\PFUser $user): UserAvatarHash
    {
        return $this->retrieveHashes($user)[0] ?? new UserAvatarHash($user, Option::nothing(\Psl\Type\string()));
    }

    #[\Override]
    public function retrieveHashes(\PFUser ...$users): array
    {
        $user_ids = \Psl\Vec\map($users, static fn (\PFUser $user): int => (int) $user->getId());
        if ($user_ids === []) {
            return [];
        }

        $users_ids_statement = EasyStatement::open()->in('user_id IN (?*)', $user_ids);

        /** @psalm-var array<int,string> $rows */
        $rows = $this->getDB()->safeQuery(
            "SELECT user_id, hash FROM user_avatar_hash WHERE hash IS NOT NULL AND $users_ids_statement",
            $users_ids_statement->values(),
            \PDO::FETCH_KEY_PAIR
        );

        $results = [];

        foreach ($users as $user) {
            $results[] = new UserAvatarHash(
                $user,
                Option::fromNullable($rows[(int) $user->getId()] ?? null)
            );
        }

        return $results;
    }

    #[\Override]
    public function store(\PFUser $user, string $hash): void
    {
        $this->getDB()->insertOnDuplicateKeyUpdate(
            'user_avatar_hash',
            [
                'user_id' => $user->getId(),
                'hash'    => $hash,
            ],
            ['hash']
        );
    }

    #[\Override]
    public function delete(\PFUser $user): void
    {
        $this->getDB()->delete('user_avatar_hash', ['user_id' => $user->getId()]);
    }
}
