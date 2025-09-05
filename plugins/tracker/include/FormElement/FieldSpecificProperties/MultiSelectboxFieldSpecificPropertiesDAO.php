<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\FieldSpecificProperties;

use Tuleap\DB\DataAccessObject;

final class MultiSelectboxFieldSpecificPropertiesDAO extends DataAccessObject implements DeleteSpecificProperties, SearchSpecificProperties, SaveSpecificFieldProperties
{
    #[\Override]
    public function deleteFieldProperties(int $field_id): void
    {
        $this->getDB()->delete('tracker_field_msb', ['field_id' => $field_id]);
    }

    /**
     * @return null | array{field_id: int, size: int}
     */
    #[\Override]
    public function searchByFieldId(int $field_id): ?array
    {
        $sql = 'SELECT *
                FROM tracker_field_msb
                WHERE field_id = ? ';

        return $this->getDB()->row($sql, $field_id);
    }

    #[\Override]
    public function saveSpecificProperties(int $field_id, array $row): void
    {
        $size = 7;
        if (isset($row['size']) && (int) $row['size']) {
            $size = $row['size'];
        }

        $sql = 'REPLACE INTO tracker_field_msb (field_id, size)
                VALUES (?, ?)';
        $this->getDB()->run($sql, $field_id, $size);
    }
}
