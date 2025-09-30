<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\Search;

final class SearchColumnCollection
{
    /**
     * @var SearchColumn[]
     */
    private array $columns = [];

    public function add(SearchColumn $column): void
    {
        $this->columns[] = $column;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function extractColumnsOnCustomProperties(): self
    {
        $only_custom_properties = new self();
        foreach ($this->columns as $column) {
            if ($column->isCustomProperty()) {
                $only_custom_properties->add($column);
            }
        }

        return $only_custom_properties;
    }

    /**
     * @return array<int, string>
     */
    public function getColumnNames(): array
    {
        return array_map(
            static fn(SearchColumn $column): string => $column->getName(),
            $this->columns,
        );
    }
}
