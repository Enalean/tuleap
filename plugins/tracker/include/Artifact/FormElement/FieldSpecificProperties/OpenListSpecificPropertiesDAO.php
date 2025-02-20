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

final class OpenListSpecificPropertiesDAO extends DataAccessObject implements DeleteSpecificProperties, SearchSpecificProperties, SaveSpecificFieldProperties
{
    public function deleteFieldProperties(int $field_id): void
    {
        $this->getDB()->delete('tracker_field_openlist', ['field_id' => $field_id]);
    }

    public function saveSpecificProperties(int $field_id, array $row): void
    {
        $hint = $row['hint'] ?? '';

        $sql = 'REPLACE INTO tracker_field_openlist (field_id, hint)
                VALUES (?, ?)';
        $this->getDB()->run($sql, $field_id, $hint);
    }

    /**
     * @return array{field_id: int, hint: string}
     */
    public function searchByFieldId(int $field_id): ?array
    {
        $sql = 'SELECT *
                FROM tracker_field_openlist
                WHERE field_id = ? ';

        return $this->getDB()->row($sql, $field_id);
    }
}
