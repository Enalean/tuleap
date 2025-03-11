<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

use Tuleap\Cryptography\ConcealedString;

/**
 * @psalm-immutable
 */
final readonly class StandardPasswordHandler implements PasswordHandler
{
    private const ALGO          = PASSWORD_ARGON2ID;
    private const ALGO_SETTINGS = ['memory_cost' => 65536, 'time_cost' => 10, 'threads' => 1];

    public function verifyHashPassword(
        ConcealedString $plain_password,
        #[\SensitiveParameter]
        string $hash_password,
    ): bool {
        return \password_verify($plain_password->getString(), $hash_password);
    }

    public function computeHashPassword(ConcealedString $plain_password): string
    {
        $password_hash = \password_hash($plain_password->getString(), self::ALGO, self::ALGO_SETTINGS);
        if (! $password_hash) {
            throw new LogicException('Could not compute password hash');
        }
        return $password_hash;
    }

    public function isPasswordNeedRehash(
        #[\SensitiveParameter]
        string $hash_password,
    ): bool {
        return \password_needs_rehash($hash_password, self::ALGO, self::ALGO_SETTINGS);
    }
}
