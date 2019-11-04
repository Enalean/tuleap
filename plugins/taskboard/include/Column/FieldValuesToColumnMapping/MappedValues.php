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
    /** @var int[] */
    private $value_ids;

    /**
     * @param int[] $value_ids
     */
    public function __construct(array $value_ids)
    {
        $this->value_ids = $value_ids;
    }

    /**
     * @return int[]
     */
    public function getValueIds(): array
    {
        return $this->value_ids;
    }

    public function isEmpty(): bool
    {
        return empty($this->value_ids);
    }

    public function getFirstValue(): int
    {
        return reset($this->value_ids);
    }
}
