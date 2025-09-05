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

use Tuleap\DB\DataAccessObject;
use Tuleap\Option\Option;

final class AvatarHashDao extends DataAccessObject implements AvatarHashStorage, AvatarHashStorageDeletor
{
    #[\Override]
    public function retrieve(\PFUser $user): Option
    {
        $hash = $this->getDB()->cell('SELECT hash FROM user_avatar_hash WHERE user_id = ?', $user->getId());
        if ($hash === false) {
            return Option::nothing(\Psl\Type\string());
        }

        return Option::fromValue($hash);
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
