<?php
/**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
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

class PasswordVerifier
{
    /**
     * @var \PasswordHandler
     */
    private $password_handler;

    public function __construct(\PasswordHandler $password_handler)
    {
        $this->password_handler = $password_handler;
    }

    /**
     * @return bool
     */
    public function verifyPassword(PFUser $user, $password)
    {
        $hashed_password        = $user->getUserPw();
        $legacy_hashed_password = $user->getLegacyUserPw();

        return $this->isPasswordValid($password, $hashed_password) ||
            $this->isLegacyPasswordValid($password, $legacy_hashed_password);
    }

    private function isPasswordValid($password, $hashed_password)
    {
        return $this->password_handler->verifyHashPassword($password, $hashed_password);
    }

    private function isLegacyPasswordValid($password, $legacy_hashed_password)
    {
        return hash_equals($legacy_hashed_password, md5($password));
    }
}
