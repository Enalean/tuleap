<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban\Widget;

use Exception;
use Tuleap\DB\DataAccessObject;

final class WidgetKanbanDao extends DataAccessObject
{
    public function createKanbanWidget(
        int $owner_id,
        string $owner_type,
        int $kanban_id,
        string $kanban_title,
        int $tracker_report_id,
    ): int {
        // The creation of a kanban widget can happen in two contexts: requested by a user and during a project creation
        // The former might leads to a nested transaction issue if the whole is running with PDO instead of deprecated
        // mysql_* API
        $does_transaction_needs_to_be_started = $this->getDB()->inTransaction() === false;
        if ($does_transaction_needs_to_be_started) {
            $this->getDB()->beginTransaction();
        }

        try {
            $widget_id = $this->create($owner_id, $owner_type, $kanban_id, $kanban_title);

            if ($widget_id && $tracker_report_id) {
                $this->createConfigForWidgetId($widget_id, $tracker_report_id);
            }

            if ($does_transaction_needs_to_be_started) {
                $this->getDB()->commit();
            }
        } catch (Exception $error) {
            if ($does_transaction_needs_to_be_started) {
                $this->getDB()->rollBack();
            }

            throw $error;
        }

        return $widget_id;
    }

    private function create(int $owner_id, string $owner_type, int $kanban_id, string $kanban_title): int
    {
        $sql = "INSERT INTO plugin_agiledashboard_kanban_widget(owner_id, owner_type, title, kanban_id)
                VALUES (?, ?, ?, ?)";

        $this->getDB()->run($sql, $owner_id, $owner_type, $kanban_title, $kanban_id);

        return (int) $this->getDB()->lastInsertId();
    }

    private function createConfigForWidgetId(int $widget_id, int $tracker_report_id): void
    {
        $sql = "
            REPLACE INTO plugin_agiledashboard_kanban_widget_config(widget_id, tracker_report_id)
            VALUES (?, ?)
        ";

        $this->getDB()->run($sql, $widget_id, $tracker_report_id);
    }

    /**
     * @return array{id: int, owner_id: int, owner_type: string, title: string, kanban_id: int}|null
     */
    public function searchWidgetById(int $id, int $owner_id, string $owner_type): ?array
    {
        $sql = "SELECT *
                FROM plugin_agiledashboard_kanban_widget
                WHERE owner_id = ?
                  AND owner_type = ?
                  AND id = ?";

        return $this->getDB()->row($sql, $owner_id, $owner_type, $id);
    }

    public function deleteKanbanWidget(int $id, int $owner_id, string $owner_type): void
    {
        $sql = 'DELETE plugin_agiledashboard_kanban_widget, plugin_agiledashboard_kanban_widget_config
                FROM plugin_agiledashboard_kanban_widget
                LEFT JOIN plugin_agiledashboard_kanban_widget_config ON
                  plugin_agiledashboard_kanban_widget_config.widget_id = plugin_agiledashboard_kanban_widget.id
                WHERE id = ? AND owner_id = ? AND owner_type = ?';

        $this->getDB()->run($sql, $id, $owner_id, $owner_type);
    }
}
