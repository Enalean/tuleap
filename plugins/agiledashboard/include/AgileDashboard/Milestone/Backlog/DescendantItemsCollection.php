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

/**
 * @template-implements Iterator<Artifact>
 */
class AgileDashboard_Milestone_Backlog_DescendantItemsCollection implements Iterator, Countable
{
    /** @var Artifact[] */
    private $items = [];

    private int $total_available_size = 0;

    public function push(Artifact $item): void
    {
        $this->items[] = $item;
    }

    #[Override]
    public function current(): Artifact
    {
        return current($this->items);
    }

    #[Override]
    public function key(): int
    {
        return key($this->items);
    }

    #[Override]
    public function next(): void
    {
        next($this->items);
    }

    #[Override]
    public function rewind(): void
    {
        reset($this->items);
    }

    #[Override]
    public function count(string $mode = 'COUNT_NORMAL'): int
    {
        return count($this->items);
    }

    #[Override]
    public function valid(): bool
    {
        return current($this->items) !== false;
    }

    public function getTotalAvaialableSize(): int
    {
        return $this->total_available_size;
    }

    public function setTotalAvaialableSize(int $size): void
    {
        $this->total_available_size = $size;
    }
}
