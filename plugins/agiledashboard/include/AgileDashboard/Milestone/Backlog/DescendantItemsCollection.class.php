<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class AgileDashboard_Milestone_Backlog_DescendantItemsCollection implements Iterator, Countable
{

    /** @var Tracker_Artifact[] */
    private $items = array();

    /** @var int */
    private $total_available_size;

    public function push(Tracker_Artifact $item)
    {
        $this->items[] = $item;
    }

    public function current()
    {
        return current($this->items);
    }

    public function key()
    {
        return key($this->items);
    }

    public function next()
    {
        next($this->items);
    }

    public function rewind()
    {
        reset($this->items);
    }

    public function count($mode = 'COUNT_NORMAL')
    {
        return count($this->items);
    }

    public function valid()
    {
        return current($this->items) !== false;
    }

    public function getTotalAvaialableSize()
    {
        return $this->total_available_size;
    }

    public function setTotalAvaialableSize($size)
    {
        $this->total_available_size = (int) $size;
    }
}
