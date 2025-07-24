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

use Cardwall_Column;
use Tuleap\Taskboard\Tracker\TaskboardTracker;

final readonly class SearchMappedFieldValuesForColumnStub implements SearchMappedFieldValuesForColumn
{
    /**
     * @param list<array{TaskboardTracker, \Cardwall_Column, list<int>}> $mapped_values
     */
    private function __construct(private array $mapped_values)
    {
    }

    /**
     * @param list<int> $bind_value_ids
     */
    public static function withValues(
        TaskboardTracker $taskboard_tracker,
        \Cardwall_Column $column,
        array $bind_value_ids,
    ): self {
        return new self([
            [$taskboard_tracker, $column, $bind_value_ids],
        ]);
    }

    /**
     * @param array{TaskboardTracker, \Cardwall_Column, list<int>} $mapped_bind_value_ids
     * @param array{TaskboardTracker, \Cardwall_Column, list<int>} ...$other_mapped_bind_value_ids
     */
    public static function withMappings(array $mapped_bind_value_ids, array ...$other_mapped_bind_value_ids): self
    {
        $mappings = [];
        foreach ([$mapped_bind_value_ids, ...$other_mapped_bind_value_ids] as $mapping) {
            $mappings[] = [$mapping[0], $mapping[1], $mapping[2]];
        }
        return new self($mappings);
    }

    public static function withNoMappedValue(): self
    {
        return new self([]);
    }

    #[\Override]
    public function searchMappedFieldValuesForColumn(
        TaskboardTracker $taskboard_tracker,
        Cardwall_Column $column,
    ): array {
        foreach ($this->mapped_values as $mapping) {
            if (
                $mapping[0]->getMilestoneTrackerId() === $taskboard_tracker->getMilestoneTrackerId()
                && $mapping[0]->getTrackerId() === $taskboard_tracker->getTrackerId()
                && $mapping[1]->getId() === $column->getId()
            ) {
                return $mapping[2];
            }
        }
        return [];
    }
}
