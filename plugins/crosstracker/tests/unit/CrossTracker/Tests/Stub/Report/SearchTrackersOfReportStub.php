<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Tests\Stub\Report;

final readonly class SearchTrackersOfReportStub implements \Tuleap\CrossTracker\Report\SearchTrackersOfReport
{
    /** @param array<int, list<int>> $trackers_map */
    private function __construct(private array $trackers_map)
    {
    }

    public function searchReportTrackersById(int $report_id): array
    {
        return $this->trackers_map[$report_id] ?? [];
    }

    /**
     * @param array{report_id: int, trackers: list<int>} $tracker_preparation
     * @param array{report_id: int, trackers: list<int>} ...$other_tracker_preparations
     * @no-named-arguments
     */
    public static function withTrackers(array $tracker_preparation, array ...$other_tracker_preparations): self
    {
        $preparations = [$tracker_preparation, ...$other_tracker_preparations];
        $trackers_map = [];
        foreach ($preparations as $preparation) {
            $trackers_map[$preparation['report_id']] = $preparation['trackers'];
        }
        return new self($trackers_map);
    }
}
