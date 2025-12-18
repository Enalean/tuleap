<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub;

use Override;
use Tuleap\Tracker\RetrieveMultipleTrackers;
use Tuleap\Tracker\Tracker;

final class RetrieveMultipleTrackersStub implements RetrieveMultipleTrackers
{
    /**
     * @param array<int, Tracker> $trackers
     */
    private function __construct(private array $trackers)
    {
    }

    #[Override]
    public function getTrackersByGroupId(int|string $project_id): array
    {
        return $this->trackers;
    }

    public static function withTrackers(Tracker $tracker, Tracker ...$other_trackers): self
    {
        $trackers = [];
        foreach ([$tracker, ...$other_trackers] as $t) {
            $trackers[$t->getId()] = $t;
        }

        return new self($trackers);
    }

    public static function withoutTrackers(): self
    {
        return new self([]);
    }
}
