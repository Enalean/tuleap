<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping;

final class MappedValues implements MappedValuesInterface
{
    /** @var list<int> */
    private $value_ids;

    /**
     * @param list<int> $value_ids
     */
    public function __construct(array $value_ids)
    {
        $this->value_ids = $value_ids;
    }

    /**
     * @return list<int>
     */
    public function getValueIds(): array
    {
        return $this->value_ids;
    }

    public function isEmpty(): bool
    {
        return empty($this->value_ids);
    }

    public function contains(int $bind_value_id): bool
    {
        return in_array($bind_value_id, $this->value_ids, true);
    }

    public function getFirstValue(): int
    {
        return reset($this->value_ids);
    }

    public function removeValue(int $value): void
    {
        $key = array_search($value, $this->value_ids);

        if ($key !== false) {
            unset($this->value_ids[$key]);
        }
    }
}
