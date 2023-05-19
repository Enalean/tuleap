<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Test\Stub;

use Tracker;
use Tuleap\Tracker\Artifact\RetrieveTracker;

final class RetrieveTrackerStub implements RetrieveTracker
{
    /**
     * @param array<int, Tracker> $trackers
     */
    private function __construct(private array $trackers)
    {
    }

    public function getTrackerById($tracker_id): ?Tracker
    {
        return $this->trackers[$tracker_id] ?? null;
    }

    public static function withoutTracker(): self
    {
        return new self([]);
    }

    public static function withTracker(Tracker $tracker): self
    {
        return self::withTrackers($tracker);
    }

    public static function withTrackers(Tracker $tracker, Tracker ...$other_trackers): self
    {
        $trackers = [];
        foreach ([$tracker, ...$other_trackers] as $t) {
            $trackers[$t->getId()] = $t;
        }

        return new self($trackers);
    }
}
