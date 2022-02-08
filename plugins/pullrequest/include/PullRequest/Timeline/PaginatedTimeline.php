<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Timeline;

class PaginatedTimeline
{
    /** @var array */
    public $events;

    /** @var int */
    public $total_size;


    public function __construct(array $events, $total_size)
    {
        $this->events     = $events;
        $this->total_size = $total_size;
    }

    public function getEvents()
    {
        return $this->events;
    }

    public function getTotalSize()
    {
        return $this->total_size;
    }
}
