<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Kanban;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;

class KanbanColumnDao extends DataAccessObject
{
    public function getColumnWipLimit(int $kanban_id, int $column_id): ?int
    {
        $sql = "SELECT wip_limit
                FROM plugin_agiledashboard_kanban_configuration_column
                WHERE kanban_id = ?
                AND value_id = ?";

        $wip = $this->getDB()->cell($sql, $kanban_id, $column_id);

        return $wip ?: null;
    }

    public function setColumnWipLimit(int $kanban_id, int $column_id, int $wip_limit): void
    {
        $sql = "REPLACE INTO plugin_agiledashboard_kanban_configuration_column (kanban_id, value_id, wip_limit)
                VALUES (?, ?, ?)";

        $this->getDB()->run($sql, $kanban_id, $column_id, $wip_limit);
    }

    /**
     * @param \Closure(): bool $before_delete
     */
    public function deleteColumn(int $kanban_id, int $column_id, \Closure $before_delete): void
    {
        $this->getDB()->tryFlatTransaction(static function (EasyDB $db) use ($kanban_id, $column_id, $before_delete) {
            if ($before_delete()) {
                $sql = "DELETE FROM plugin_agiledashboard_kanban_configuration_column
                WHERE kanban_id = ?
                AND value_id = ?";

                $db->run($sql, $kanban_id, $column_id);
            }
        });
    }
}
