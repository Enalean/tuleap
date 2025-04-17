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
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Test\Stub\Semantic\Title;

use Tuleap\Tracker\Semantic\Title\SearchTrackersWithoutTitleSemantic;

final class SearchTrackersWithoutTitleSemanticStub implements SearchTrackersWithoutTitleSemantic
{
    /**
     * @param list<int> $tracker_ids_without_title
     */
    private function __construct(
        private array $tracker_ids_without_title,
    ) {
    }

    public static function withAllTrackersHaveTitle(): self
    {
        return new self([]);
    }

    /**
     * @no-named-arguments
     */
    public static function withTrackersThatDoNotHaveTitle(int $tracker_id, int ...$other_tracker_ids): self
    {
        return new self([$tracker_id, ...$other_tracker_ids]);
    }

    public function countTrackersWithoutTitleSemantic(array $tracker_ids): int
    {
        return count($this->getTrackerIdsWithoutTitleSemantic($tracker_ids));
    }

    public function getTrackerIdsWithoutTitleSemantic(array $tracker_ids): array
    {
        return array_values(array_intersect($this->tracker_ids_without_title, $tracker_ids));
    }
}
