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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle;

use Tuleap\Option\Option;
use Tuleap\Taskboard\Tracker\TaskboardTracker;

final readonly class SearchMappedFieldStub implements SearchMappedField
{
    /** @param list<array{TaskboardTracker, int}> $mapped_field_ids */
    private function __construct(private array $mapped_field_ids)
    {
    }

    public static function withMappedField(TaskboardTracker $taskboard_tracker, int $mapped_field_id): self
    {
        return new self([
            [$taskboard_tracker, $mapped_field_id],
        ]);
    }

    /**
     * @param array{TaskboardTracker, int} $mapped_field_ids
     * @param array{TaskboardTracker, int} ...$other_mapped_field_ids
     */
    public static function withMappedFields(array $mapped_field_ids, array ...$other_mapped_field_ids): self
    {
        $mappings = [];
        foreach ([$mapped_field_ids, ...$other_mapped_field_ids] as $mapping) {
            $mappings[] = [$mapping[0], $mapping[1]];
        }
        return new self($mappings);
    }

    public static function withNoField(): self
    {
        return new self([]);
    }

    #[\Override]
    public function searchMappedField(TaskboardTracker $taskboard_tracker): Option
    {
        foreach ($this->mapped_field_ids as $mapping) {
            if (
                $mapping[0]->getMilestoneTrackerId() === $taskboard_tracker->getMilestoneTrackerId()
                && $mapping[0]->getTrackerId() === $taskboard_tracker->getTrackerId()
            ) {
                return Option::fromValue($mapping[1]);
            }
        }
        return Option::nothing(\Psl\Type\int());
    }
}
