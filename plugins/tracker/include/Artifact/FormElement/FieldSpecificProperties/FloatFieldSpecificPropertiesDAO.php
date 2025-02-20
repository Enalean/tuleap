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

namespace Tuleap\Tracker\Artifact\FormElement\FieldSpecificProperties;

use Tuleap\DB\DataAccessObject;

final class FloatFieldSpecificPropertiesDAO extends DataAccessObject implements DuplicateSpecificProperties, DeleteSpecificProperties, SearchSpecificProperties
{
    public function duplicate(int $from_field_id, int $to_field_id): void
    {
        $sql = 'REPLACE INTO tracker_field_float (field_id, maxchars, size, default_value)
                SELECT ?, maxchars, size, default_value FROM tracker_field_float WHERE field_id = ?';
        $this->getDB()->run($sql, $to_field_id, $from_field_id);
    }

    public function deleteFieldProperties(int $field_id): void
    {
        $this->getDB()->delete('tracker_field_float', ['field_id' => $field_id]);
    }

    /**
     * @return array{field_id: int, maxchars: int, size: int, default_value: float}
     */
    public function searchByFieldId(int $field_id): ?array
    {
        $sql = 'SELECT *
                FROM tracker_field_float
                WHERE field_id = ? ';

        return $this->getDB()->row($sql, $field_id);
    }
}
