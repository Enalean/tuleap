<?php
/**
 * Copyright Enalean (c) 2019 - present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Timetracking\REST\v1;

class TimetrackingTrackerUserRepresentation
{
    /**
     * @var string
     */
    public $user_name;

    /**
     * @var int
     */
    public $user_id;

    /**
     * @var int
     */
    public $minutes;

    private function __construct(string $user_name, int $user_id, int $minutes)
    {
        $this->user_name = $user_name;
        $this->user_id   = $user_id;
        $this->minutes   = $minutes;
    }

    public static function build(string $user_name, int $user_id, int $minutes): TimetrackingTrackerUserRepresentation
    {
        return new self($user_name, $user_id, $minutes);
    }
}
