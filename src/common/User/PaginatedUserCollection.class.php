<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class PaginatedUserCollection implements Countable
{

    /** @var PFUser[] */
    private $users;

    /** @var int */
    private $total_count;

    /**
     * @param PFUser[] $users
     * @param int $total_count
     */
    public function __construct(array $users, $total_count)
    {
        $this->users       = $users;
        $this->total_count = $total_count;
    }

    /**
     * @return PFUser[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        return $this->total_count;
    }

    /**
     * @see Countable
     *
     * @return int
     */
    public function count()
    {
        return count($this->users);
    }
}
