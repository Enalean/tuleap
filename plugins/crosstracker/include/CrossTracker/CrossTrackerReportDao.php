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

declare(strict_types=1);

namespace Tuleap\CrossTracker;

use Tuleap\CrossTracker\Report\CloneReport;
use Tuleap\CrossTracker\Report\CreateReport;
use Tuleap\CrossTracker\Report\RetrieveReport;
use Tuleap\DB\DataAccessObject;

class CrossTrackerReportDao extends DataAccessObject implements SearchCrossTrackerWidget, CreateReport, RetrieveReport, CloneReport
{
    public function searchReportById(int $report_id): ?array
    {
        $sql = 'SELECT *
                FROM plugin_crosstracker_query
                WHERE id = ?';

        return $this->getDB()->row($sql, $report_id);
    }

    public function createReportFromExpertQuery(string $query): int
    {
        return (int) $this->getDB()->insertReturnId('plugin_crosstracker_query', [
            'query' => $query,
            'title' => '',
        ]);
    }

    public function updateQuery($report_id, $expert_query): void
    {
        $sql = 'REPLACE INTO plugin_crosstracker_query (id, query) VALUES (?, ?)';
        $this->getDB()->run($sql, $report_id, $expert_query);
    }

    public function delete($report_id): void
    {
        $sql = 'DELETE FROM plugin_crosstracker_query WHERE id = ?';
        $this->getDB()->run($sql, $report_id);
    }

    /**
     * @psalm-return array{dashboard_id: int, dashboard_type: string, user_id: int, project_id: int}|null
     */
    public function searchCrossTrackerWidgetByCrossTrackerReportId($content_id): ?array
    {
        $sql = "SELECT dashboard_id, dashboard_type, user_id, project_dashboards.project_id
                  FROM plugin_crosstracker_query
                INNER JOIN dashboards_lines_columns_widgets AS widget
                    ON plugin_crosstracker_query.id = widget.content_id
                INNER JOIN dashboards_lines_columns
                    ON widget.column_id = dashboards_lines_columns.id
                INNER JOIN dashboards_lines
                    ON dashboards_lines_columns.line_id = dashboards_lines.id
                LEFT JOIN user_dashboards
                    ON user_dashboards.id = dashboards_lines.dashboard_id
                LEFT JOIN project_dashboards
                    ON project_dashboards.id = dashboards_lines.dashboard_id
                WHERE plugin_crosstracker_query.id = ?
                  AND widget.name = 'crosstrackersearch';";

        return $this->getDB()->row($sql, $content_id);
    }

    public function cloneReport(int $template_report_id): int
    {
        $sql = <<<EOSQL
        INSERT INTO plugin_crosstracker_query (id, query, title, description)
        SELECT NULL, report.query, report.title, report.description
        FROM plugin_crosstracker_query AS report
        WHERE report.id = ?
        EOSQL;

        $this->getDB()->run($sql, $template_report_id);
        return (int) $this->getDB()->lastInsertId('plugin_crosstracker_query');
    }
}
