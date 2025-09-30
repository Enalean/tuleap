<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Renderer\Table\Sort;

use Tuleap\Tracker\FormElement\Field\TrackerField;

/**
 * @psalm-immutable
 */
final class SortWithIntegrityChecked
{
    /**
     * @psalm-param  array{array{field: TrackerField, field_id: int, is_desc: bool}} $sort
     * @psalm-return array<array{field: TrackerField, field_id: int, is_desc: bool}>
     */
    public static function getSortOnUsedFields(array $sort): array
    {
        $valid_sort = [];
        foreach ($sort as $s) {
            if (isset($s['field']) && $s['field'] instanceof TrackerField && $s['field']->isUsed()) {
                $valid_sort[] = $s;
            }
        }

        return $valid_sort;
    }

    /**
     * @psalm-param  array{array{field: TrackerField, field_id: int, is_desc: bool}} $sort
     * @psalm-return array<array{field: TrackerField, field_id: int, is_desc: bool}>
     */
    public static function getSort(array $sort): array
    {
        $valid_sort = [];
        foreach ($sort as $s) {
            if (isset($s['field']) && $s['field'] instanceof TrackerField) {
                $valid_sort[] = $s;
            }
        }

        return $valid_sort;
    }
}
