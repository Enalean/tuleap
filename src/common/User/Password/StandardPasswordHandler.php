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

class StandardPasswordHandler implements PasswordHandler
{
    public function verifyHashPassword(ConcealedString $plain_password, string $hash_password): bool
    {
        return password_verify($plain_password->getString(), $hash_password);
    }

    public function computeHashPassword(ConcealedString $plain_password): string
    {
        $password_hash = password_hash($plain_password->getString(), PASSWORD_DEFAULT);
        if (! $password_hash) {
            throw new LogicException('Could not compute password hash');
        }
        return $password_hash;
    }

    public function isPasswordNeedRehash(string $hash_password): bool
    {
        return password_needs_rehash($hash_password, PASSWORD_DEFAULT);
    }

    public function computeUnixPassword(ConcealedString $plain_password): string
    {
        $number_generator = new RandomNumberGenerator(self::SALT_SIZE);
        $salt             = $number_generator->getNumber();
        // We use SHA-512 with 5000 rounds to create the Unix Password
        // SHA-512 is more widely available than BCrypt in GLibc OS library
        // Only 5000 rounds are used (which is the default value) to keep reasonable performance
        return crypt($plain_password->getString(), '$6$rounds=5000$' . $salt . '$');
    }
}
