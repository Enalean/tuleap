<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Widget;

use Tuleap\DB\DataAccessObject;

final class CrossTrackerWidgetDao extends DataAccessObject implements SearchCrossTrackerWidget, CreateWidget, CloneWidget
{
    public function searchWidgetExistence(int $widget_id): bool
    {
        return $this->getDB()->row('SELECT 1 FROM plugin_crosstracker_widget WHERE id = ?', $widget_id) !== null;
    }

    /**
     * @psalm-return array{dashboard_id: int, dashboard_type: string, user_id: int, project_id: int}|null
     */
    public function searchCrossTrackerWidgetDashboardById(int $content_id): ?array
    {
        $sql = "SELECT dashboard_id, dashboard_type, user_id, project_dashboards.project_id
                  FROM plugin_crosstracker_widget
                INNER JOIN dashboards_lines_columns_widgets AS widget
                    ON plugin_crosstracker_widget.id = widget.content_id
                INNER JOIN dashboards_lines_columns
                    ON widget.column_id = dashboards_lines_columns.id
                INNER JOIN dashboards_lines
                    ON dashboards_lines_columns.line_id = dashboards_lines.id
                LEFT JOIN user_dashboards
                    ON user_dashboards.id = dashboards_lines.dashboard_id
                LEFT JOIN project_dashboards
                    ON project_dashboards.id = dashboards_lines.dashboard_id
                WHERE plugin_crosstracker_widget.id = ?
                  AND widget.name = 'crosstrackersearch';";

        return $this->getDB()->row($sql, $content_id);
    }

    public function createWidget(): int
    {
        return (int) $this->getDB()->insertReturnId('plugin_crosstracker_widget', []);
    }

    public function cloneWidget(int $template_widget_id): int
    {
        return $this->getDB()->tryFlatTransaction(function () use ($template_widget_id) {
            $new_widget_id = $this->createWidget();

            $queries = $this->getDB()->run('SELECT query, title, description, is_default FROM plugin_crosstracker_query WHERE widget_id = ?', $template_widget_id);
            foreach ($queries as $query) {
                $this->getDB()->insert('plugin_crosstracker_query', [
                    'id'          => $this->uuid_factory->buildUUIDBytes(),
                    'widget_id'   => $new_widget_id,
                    'query'       => $query['query'],
                    'title'       => $query['title'],
                    'description' => $query['description'],
                    'is_default'  => $query['is_default'],
                ]);
            }

            return $new_widget_id;
        });
    }

    public function deleteWidget(int $widget_id): void
    {
        $this->getDB()->tryFlatTransaction(function () use ($widget_id) {
            $this->getDB()->run('DELETE FROM plugin_crosstracker_query WHERE widget_id = ?', $widget_id);
            $this->getDB()->run('DELETE FROM plugin_crosstracker_widget WHERE id = ?', $widget_id);
        });
    }
}
