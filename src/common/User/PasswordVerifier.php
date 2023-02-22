<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

namespace Tuleap\User;

use PFUser;
use Tuleap\Cryptography\ConcealedString;

class PasswordVerifier
{
    public function __construct(private \PasswordHandler $password_handler)
    {
    }

    public function verifyPassword(PFUser $user, ConcealedString $password): bool
    {
        $hashed_password = $user->getUserPw();
        if ($hashed_password === null) {
            return false;
        }

        return $this->isPasswordValid($password, $hashed_password);
    }

    private function isPasswordValid(ConcealedString $password, string $hashed_password): bool
    {
        return $this->password_handler->verifyHashPassword($password, $hashed_password);
    }
}
