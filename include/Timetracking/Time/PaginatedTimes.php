<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
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

namespace Tuleap\Timetracking\Time;

class PaginatedTimes
{
    /**
     * @var Time[]
     */
    private $times;

    /**
     * @var int
     */
    private $total_size;

    /**
     *
     * @param Time[] $times
     * @param int $total_size
     */
    public function __construct(array $times, $total_size) {
        $this->times      = $times;
        $this->total_size = $total_size;
    }

    public function getTotalSize() {
        return $this->total_size;
    }

    /**
     * @return Time[]
     */
    public function getTimes() {
        return $this->times;
    }
}
