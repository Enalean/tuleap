<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\List;

use RuntimeException;
use Tuleap\DB\DataAccessObject;

class OpenListValueDao extends DataAccessObject
{
    /**
     * @return ?array{
     *     id: int,
     *     field_id: int,
     *     label: string,
     *     is_hidden: int,
     * }
     */
    public function searchById(int $field_id, int $id): ?array
    {
        $sql = 'SELECT id, field_id, label, is_hidden FROM tracker_field_openlist_value WHERE field_id = ? AND id = ?';
        return $this->getDB()->row($sql, $field_id, $id);
    }

    /**
     * @return array{
     *      id: int,
     *      field_id: int,
     *      label: string,
     *      is_hidden: int,
     *  }[]
     */
    public function searchByFieldId(int $field_id): array
    {
        $sql = 'SELECT id, field_id, label, is_hidden FROM tracker_field_openlist_value WHERE field_id = ?';
        return $this->getDB()->q($sql, $field_id);
    }

    public function create(int $field_id, string $label): int
    {
        $sql = <<<SQL
        INSERT INTO tracker_field_openlist_value (field_id, label) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE label=label;
        SQL;
        $this->getDB()->safeQuery($sql, [$field_id, $label]);

        $row = $this->searchByExactLabel($field_id, $label);
        if ($row === null) {
            throw new RuntimeException('Failed to find openlist value even though it has just been added');
        }
        return $row['id'];
    }

    /**
     * @return array{
     *      id: int,
     *      field_id: int,
     *      label: string,
     *      is_hidden: int,
     *  }[]
     */
    public function searchByKeyword(int $field_id, string $keyword, int $limit = 10): array
    {
        $sql = <<<SQL
            SELECT id, field_id, label, is_hidden FROM tracker_field_openlist_value
            WHERE field_id = ? AND is_hidden != 1 AND label LIKE ? LIMIT ?
            SQL;
        return $this->getDB()->safeQuery($sql, [
            $field_id,
            '%' . $this->getDB()->escapeLikeValue($keyword) . '%',
            $limit,
        ]);
    }

    /**
     * @return ?array{
     *     id: int,
     *     field_id: int,
     *     label: string,
     *     is_hidden: int,
     * }
     */
    public function searchByExactLabel(int $field_id, string $label): ?array
    {
        $sql = <<<SQL
            SELECT id, field_id, label, is_hidden FROM tracker_field_openlist_value
            WHERE field_id = ? AND label = ?
            SQL;
        return $this->getDB()->row($sql, $field_id, $label);
    }

    public function updateOpenValue(int $id, bool $is_hidden, string $label): void
    {
        $this->getDB()->update('tracker_field_openlist_value', [
            'is_hidden' => $is_hidden,
            'label'     => $label,
        ], [
            'id' => $id,
        ]);
    }
}
