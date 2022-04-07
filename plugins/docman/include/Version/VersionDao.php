<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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

namespace Tuleap\Docman\Version;

use Tuleap\DB\DataAccessObject;

class VersionDao extends DataAccessObject
{
    /**
     * @psalm-return list<array{number: int, label: string, filename: string}>
     */
    public function searchByItemId(int $id, int $offset, int $limit): array
    {
        $sql =
            "SELECT number, label, filename
                FROM plugin_docman_version WHERE item_id = ? ORDER BY number DESC LIMIT ?, ?";

        return $this->getDB()->run($sql, $id, $offset, $limit);
    }

    public function countByItemId(int $id): int
    {
        return $this->getDB()->single(
            'SELECT COUNT(*) AS nb FROM plugin_docman_version WHERE item_id = ?',
            [$id]
        );
    }
}
