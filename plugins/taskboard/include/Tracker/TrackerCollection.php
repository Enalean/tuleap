<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Taskboard\Tracker;

use Closure;

final class TrackerCollection
{
    /** @var TaskboardTracker[] */
    private $trackers;

    /**
     * @param TaskboardTracker[] $trackers
     */
    public function __construct(array $trackers)
    {
        $this->trackers = $trackers;
    }

    /**
     * @template U
     * @psalm-param Closure(TaskboardTracker):U $closure
     * @psalm-return list<U>
     */
    public function map(Closure $closure): array
    {
        $new = [];
        foreach ($this->trackers as $tracker) {
            $new[] = $closure($tracker);
        }
        return $new;
    }

    /**
     * @template     U
     * @psalm-param  Closure(mixed, TaskboardTracker):U $closure
     * @psalm-param  U $initial
     * @psalm-return U
     */
    public function reduce(Closure $closure, $initial)
    {
        return array_reduce($this->trackers, $closure, $initial);
    }
}
