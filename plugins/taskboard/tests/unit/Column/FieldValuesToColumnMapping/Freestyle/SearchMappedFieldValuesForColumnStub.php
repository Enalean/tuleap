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
     * @param list<int> $bind_value_ids
     */
    private function __construct(private array $bind_value_ids)
    {
    }

    /**
     * @param list<int> $bind_value_ids
     */
    public static function withValues(array $bind_value_ids): self
    {
        return new self($bind_value_ids);
    }

    public static function withNoMappedValue(): self
    {
        return new self([]);
    }

    public function searchMappedFieldValuesForColumn(
        TaskboardTracker $taskboard_tracker,
        Cardwall_Column $column,
    ): array {
        return $this->bind_value_ids;
    }
}
