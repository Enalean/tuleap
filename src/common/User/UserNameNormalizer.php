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

class UserNameNormalizer
{
    public function __construct(private Rule_UserName $username_rule, private Slugify $slugify)
    {
    }

    /**
     * @throws DataIncompatibleWithUsernameGenerationException
     */
    public function normalize(string $username): string
    {
        $username = $this->slugify->slugify($username, "_");

        if (! $this->username_rule->isUnixValid($username)) {
            throw new DataIncompatibleWithUsernameGenerationException();
        }

        if ($this->username_rule->isValid($username)) {
            return $username;
        }

        $username_suffix = 1;

        while (! $this->username_rule->isValid("$username$username_suffix")) {
            $username_suffix++;
        }

        return "$username$username_suffix";
    }
}
