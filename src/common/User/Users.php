<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

/**
 * First class collection of users
 */
class Users
{
    /**
     * @var PFUser[]
     */
    private $users;

    public function __construct(PFUser ...$users)
    {
        $this->users = $users;
    }

    /**
     * @return string[]
     */
    public function getNames()
    {
        $names = [];

        foreach ($this->users as $user) {
            $names[] = $user->getUserName();
        }

        return $names;
    }
}
