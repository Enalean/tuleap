<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Log;

use Tuleap\DB\DataAccessObject;

final class LogDao extends DataAccessObject implements IRetrieveStoredLog
{
    /**
     * @return array{time: int, group_id: int, user_id: int, type: int, old_value: string|null, new_value: string|null, field: string|null}[]
     */
    #[\Override]
    public function searchByItemIdOrderByTimestamp(int $item_id): array
    {
        $sql = 'SELECT time, group_id, user_id, type, old_value, new_value, field
                FROM plugin_docman_log
                WHERE item_id = ?
                ORDER BY time DESC';

        return $this->getDB()->run($sql, $item_id);
    }

    /**
     * @return array{time: int, group_id: int, user_id: int, type: int, old_value: string|null, new_value: string|null, field: string|null}[]
     */
    #[\Override]
    public function paginatedSearchByItemIdOrderByTimestamp(int $item_id, int $limit, int $offset): array
    {
        $sql = 'SELECT time, group_id, user_id, type, old_value, new_value, field
                FROM plugin_docman_log
                WHERE item_id = ?
                ORDER BY time DESC
                LIMIT ? OFFSET ?';

        return $this->getDB()->run($sql, $item_id, $limit, $offset);
    }

    #[\Override]
    public function countByItemId(int $item_id): int
    {
        $sql = 'SELECT count(*) as nb FROM plugin_docman_log WHERE item_id = ?';

        return (int) $this->getDB()->cell($sql, $item_id);
    }
}
