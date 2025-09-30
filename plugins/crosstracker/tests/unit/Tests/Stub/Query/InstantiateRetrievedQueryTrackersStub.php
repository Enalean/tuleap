<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Tests\Stub\Query;

use Tuleap\CrossTracker\Query\InstantiateRetrievedQueryTrackers;
use Tuleap\Tracker\Tracker;

final readonly class InstantiateRetrievedQueryTrackersStub implements InstantiateRetrievedQueryTrackers
{
    private function __construct(private array $trackers)
    {
    }

    public static function withTrackers(Tracker $tracker, Tracker ...$other_tracker): self
    {
        return new self([$tracker, ...$other_tracker]);
    }

    public static function withNoTrackers(): self
    {
        return new self([]);
    }

    #[\Override]
    public function getTrackers(array $trackers_ids): array
    {
        return $this->trackers;
    }
}
