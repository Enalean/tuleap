<?php
/**
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Tuleap\DB\DataAccessObject;

class WidgetKanbanConfigDAO extends DataAccessObject
{
    public function searchKanbanTrackerReportId(int $kanban_widget_id): ?int
    {
        $sql = "
            SELECT tracker_report_id
            FROM plugin_agiledashboard_kanban_widget_config
            WHERE widget_id = ?
        ";

        return $this->getDB()->single($sql, [$kanban_widget_id]) ?: null;
    }

    public function createNewConfigForWidgetId(int $widget_id, int $tracker_report_id): void
    {
        $sql = "
            REPLACE INTO plugin_agiledashboard_kanban_widget_config(widget_id, tracker_report_id)
            VALUES (?, ?)
        ";

        $this->getDB()->run($sql, $widget_id, $tracker_report_id);
    }

    public function deleteConfigForWidgetId(int $widget_id): void
    {
        $sql = "
            DELETE FROM plugin_agiledashboard_kanban_widget_config
            WHERE widget_id = ?
        ";

        $this->getDB()->run($sql, $widget_id);
    }

    public function deleteConfigurationForWidgetMatchingReportId(int $report_id): void
    {
        $sql = "
            DELETE FROM plugin_agiledashboard_kanban_widget_config
            WHERE tracker_report_id = ?
        ";

        $this->getDB()->run($sql, $report_id);
    }
}
