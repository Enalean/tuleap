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

namespace Tuleap\Tracker\Test\Stub\Semantic\Description;

use Tuleap\Tracker\Semantic\Description\SearchTrackersWithoutDescriptionSemantic;

final readonly class SearchTrackersWithoutDescriptionSemanticStub implements SearchTrackersWithoutDescriptionSemantic
{
    /**
     * @param list<int> $tracker_ids_without_description
     */
    private function __construct(
        private array $tracker_ids_without_description,
    ) {
    }

    public static function withAllTrackersHaveDescription(): self
    {
        return new self([]);
    }

    /**
     * @no-named-arguments
     */
    public static function withTrackersThatDoNotHaveDescription(int $tracker_id, int ...$other_tracker_ids): self
    {
        return new self([$tracker_id, ...$other_tracker_ids]);
    }

    #[\Override]
    public function countTrackersWithoutDescriptionSemantic(array $tracker_ids): int
    {
        return count($this->getTrackerIdsWithoutDescriptionSemantic($tracker_ids));
    }

    #[\Override]
    public function getTrackerIdsWithoutDescriptionSemantic(array $tracker_ids): array
    {
        return array_values(array_intersect($this->tracker_ids_without_description, $tracker_ids));
    }
}
