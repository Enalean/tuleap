<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

use Cocur\Slugify\Slugify;
use Rule_UserName;

readonly class UserNameNormalizer
{
    public function __construct(private Rule_UserName $username_rule, private Slugify $slugify)
    {
    }

    /**
     * @throws DataIncompatibleWithUsernameGenerationException
     */
    public function normalize(string $username): string
    {
        $username        = $this->slugify->slugify($username, '_');
        $username_length = mb_strlen($username);
        $username_suffix = 1;

        if ($username_length < Rule_UserName::USERNAME_MIN_LENGTH) {
            $username_suffix = (int) str_repeat('1', Rule_UserName::USERNAME_MIN_LENGTH - $username_length);
            $username        = $username . $username_suffix;
        }

        if ($this->username_rule->isReservedName($username) || ! $this->username_rule->isUnixValid($username)) {
            throw new DataIncompatibleWithUsernameGenerationException($this->username_rule->getErrorMessage());
        }

        if ($this->username_rule->isValid($username)) {
            return $username;
        }

        while ($username_suffix < PHP_INT_MAX) {
            $username_suffix_length   = mb_strlen("$username_suffix");
            $total_length_with_prefix = $username_length + $username_suffix_length;
            $username_prefix          = $username;
            if ($total_length_with_prefix > Rule_UserName::USERNAME_MAX_LENGTH) {
                $username_prefix = mb_substr($username, 0, $username_length - ($total_length_with_prefix - Rule_UserName::USERNAME_MAX_LENGTH));
            }

            $suffixed_username = $username_prefix . $username_suffix;

            if ($this->username_rule->isValid($suffixed_username)) {
                return $suffixed_username;
            }
        }

        throw new DataIncompatibleWithUsernameGenerationException();
    }
}
