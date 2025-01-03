<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;

class AgileDashboard_Milestone_Backlog_DescendantItemsCollection implements Iterator, Countable
{
    /** @var Artifact[] */
    private $items = [];

    /** @var int */
    private $total_available_size;

    public function push(Artifact $item)
    {
        $this->items[] = $item;
    }

    public function current(): Artifact
    {
        return current($this->items);
    }

    public function key(): int
    {
        return key($this->items);
    }

    public function next(): void
    {
        next($this->items);
    }

    public function rewind(): void
    {
        reset($this->items);
    }

    public function count($mode = 'COUNT_NORMAL'): int
    {
        return count($this->items);
    }

    public function valid(): bool
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
