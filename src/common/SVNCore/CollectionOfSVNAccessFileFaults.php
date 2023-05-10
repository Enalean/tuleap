<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\SVNCore;

use Tuleap\NeverThrow\Fault;

/**
 * @template-implements \Iterator<Fault>
 */
final class CollectionOfSVNAccessFileFaults implements \Iterator
{
    /**
     * @var array<Fault>
     */
    private array $faults;
    private int $index = 0;

    public function add(Fault $fault): void
    {
        $this->faults[] = $fault;
    }

    public function current(): Fault
    {
        return $this->faults[$this->index];
    }

    public function next(): void
    {
        $this->index++;
    }

    public function key(): int
    {
        return $this->index;
    }

    public function valid(): bool
    {
        return isset($this->faults[$this->index]);
    }

    public function rewind(): void
    {
        $this->index = 0;
    }
}
