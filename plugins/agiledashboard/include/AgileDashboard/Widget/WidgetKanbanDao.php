<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Widget;

use Exception;
use Tuleap\DB\DataAccessObject;

class WidgetKanbanDao extends DataAccessObject
{
    public function createKanbanWidget($owner_id, $owner_type, $kanban_id, $kanban_title, $tracker_report_id)
    {
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

    private function create($owner_id, $owner_type, $kanban_id, $kanban_title)
    {
        $sql = "INSERT INTO plugin_agiledashboard_kanban_widget(owner_id, owner_type, title, kanban_id)
                VALUES (?, ?, ?, ?)";

        $this->getDB()->run($sql, $owner_id, $owner_type, $kanban_title, $kanban_id);

        return $this->getDB()->lastInsertId();
    }

    private function createConfigForWidgetId($widget_id, $tracker_report_id)
    {
        $sql = "
            REPLACE INTO plugin_agiledashboard_kanban_widget_config(widget_id, tracker_report_id)
            VALUES (?, ?)
        ";

        $this->getDB()->run($sql, $widget_id, $tracker_report_id);
    }

    public function searchWidgetById($id, $owner_id, $owner_type)
    {
        $sql = "SELECT *
                FROM plugin_agiledashboard_kanban_widget
                WHERE owner_id = ?
                  AND owner_type = ?
                  AND id = ?";

        return $this->getDB()->run($sql, $owner_id, $owner_type, $id);
    }

    public function deleteKanbanWidget($id, $owner_id, $owner_type)
    {
        $sql = 'DELETE plugin_agiledashboard_kanban_widget, plugin_agiledashboard_kanban_widget_config
                FROM plugin_agiledashboard_kanban_widget
                LEFT JOIN plugin_agiledashboard_kanban_widget_config ON
                  plugin_agiledashboard_kanban_widget_config.widget_id = plugin_agiledashboard_kanban_widget.id
                WHERE id = ? AND owner_id = ? AND owner_type = ?';

        $this->getDB()->run($sql, $id, $owner_id, $owner_type);
    }
}
