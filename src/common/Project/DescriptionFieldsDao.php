<?php
/**
 * Copyright (c) Enalean, 2016-present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

declare(strict_types=1);

namespace Tuleap\Project;

use Tuleap\DB\DataAccessObject;

class DescriptionFieldsDao extends DataAccessObject
{
    /**
     * @psalm-return list<array{group_desc_id: int, desc_required: int, desc_name: string, desc_description: string, desc_rank: int, desc_type: string}>
     */
    public function searchAll(): array
    {
        $sql = 'SELECT * FROM group_desc ORDER BY desc_rank';

        return $this->getDB()->run($sql);
    }

    public function isFieldExisting(int $field_id, string $field_name): bool
    {
        $sql = 'SELECT NULL
                FROM group_desc
                WHERE group_desc_id = ?
                    AND desc_name = ?';

        $rows = $this->getDB()->run($sql, $field_id, $field_name);

        return count($rows) > 0;
    }

    public function searchFieldsWithPagination(int $limit, int $offset): array
    {
        $sql = 'SELECT * FROM group_desc ORDER BY desc_rank LIMIT ? OFFSET ?';

        return $this->getDB()->run($sql, $limit, $offset);
    }
}
