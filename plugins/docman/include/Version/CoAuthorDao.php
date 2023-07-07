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

class CoAuthorDao extends DataAccessObject
{
    /**
     * @psalm-return list<array{version_id: int, user_id: int}>
     */
    public function searchByVersionId(int $id): array
    {
        $sql = "SELECT version_id, user_id FROM plugin_docman_version_coauthor WHERE version_id = ?";

        return $this->getDB()->run($sql, $id);
    }

    /**
     * @param non-empty-list<int> $co_author_ids
     */
    public function saveVersionCoAuthors(int $id, array $co_author_ids): void
    {
        $rows = [];

        foreach (array_unique($co_author_ids) as $co_author_id) {
            $rows[] = ['version_id' => $id, 'user_id' => $co_author_id];
        }

        $this->getDB()->insertMany(
            'plugin_docman_version_coauthor',
            $rows
        );
    }
}
