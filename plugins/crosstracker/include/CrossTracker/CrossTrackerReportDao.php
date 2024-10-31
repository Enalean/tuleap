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

use ParagonIE\EasyDB\EasyDB;
use Tuleap\CrossTracker\Report\CreateReport;
use Tuleap\CrossTracker\Report\RetrieveReport;
use Tuleap\CrossTracker\Report\SearchTrackersOfReport;
use Tuleap\DB\DataAccessObject;

class CrossTrackerReportDao extends DataAccessObject implements SearchCrossTrackerWidget, CreateReport, RetrieveReport, SearchTrackersOfReport
{
    public function searchReportById(int $report_id): ?array
    {
        $sql = 'SELECT *
                FROM plugin_crosstracker_report
                WHERE id = ?';

        return $this->getDB()->row($sql, $report_id);
    }

    public function searchReportTrackersById(int $report_id): array
    {
        $sql = 'SELECT report_tracker.tracker_id
                  FROM plugin_crosstracker_report AS report
                  INNER JOIN plugin_crosstracker_report_tracker AS report_tracker
                          ON report.id = report_tracker.report_id
                 WHERE report_id = ?';

        return $this->getDB()->col($sql, 0, $report_id);
    }

    public function create()
    {
        return $this->getDB()->insertReturnId('plugin_crosstracker_report', [
            'expert_mode' => 0,
        ]);
    }

    public function createReportFromExpertQuery(string $query): int
    {
        return (int) $this->getDB()->insertReturnId('plugin_crosstracker_report', [
            'expert_query' => $query,
        ]);
    }

    public function updateReport($report_id, array $trackers, $expert_query, bool $expert_mode)
    {
        $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($expert_query, $trackers, $expert_mode, $report_id) {
            $db->run('DELETE FROM plugin_crosstracker_report_tracker WHERE report_id = ?', $report_id);
            if (! $expert_mode) {
                $this->addTrackersToReport($trackers, $report_id);
            }
            $this->updateExpertQuery($report_id, $expert_query, $expert_mode);
        });
    }

    private function updateExpertQuery($report_id, $expert_query, bool $expert_mode)
    {
        $sql = 'REPLACE INTO plugin_crosstracker_report (id, expert_query, expert_mode) VALUES (?, ?, ?)';
        $this->getDB()->run($sql, $report_id, $expert_query, $expert_mode);
    }

    /**
     * @param       $report_id
     */
    public function addTrackersToReport(array $trackers, $report_id)
    {
        $data_to_insert = [];
        foreach ($trackers as $tracker) {
            $data_to_insert[] = ['report_id' => $report_id, 'tracker_id' => $tracker->getId()];
        }

        if (! empty($data_to_insert)) {
            $this->getDB()->insertMany('plugin_crosstracker_report_tracker', $data_to_insert);
        }
    }

    public function searchTrackersIdUsedByCrossTrackerByProjectId($project_id)
    {
        $sql = 'SELECT tracker.id
                FROM plugin_crosstracker_report_tracker AS report
                INNER JOIN tracker ON report.tracker_id = tracker.id
                WHERE tracker.group_id = ?';

        return $this->getDB()->run($sql, $project_id);
    }

    public function delete($report_id)
    {
        $sql = 'DELETE report.*, tracker_report.*
                FROM plugin_crosstracker_report AS report
                  LEFT JOIN plugin_crosstracker_report_tracker AS tracker_report
                    ON (report.id = tracker_report.report_id)
                  WHERE report.id = ?';

        return $this->getDB()->run($sql, $report_id);
    }

    /**
     * @psalm-return array{dashboard_id: int, dashboard_type: string, user_id: int, project_id: int}|null
     */
    public function searchCrossTrackerWidgetByCrossTrackerReportId($content_id): ?array
    {
        $sql = "SELECT dashboard_id, dashboard_type, user_id, project_dashboards.project_id
                  FROM plugin_crosstracker_report
                INNER JOIN dashboards_lines_columns_widgets AS widget
                    ON plugin_crosstracker_report.id = widget.content_id
                INNER JOIN dashboards_lines_columns
                    ON widget.column_id = dashboards_lines_columns.id
                INNER JOIN dashboards_lines
                    ON dashboards_lines_columns.line_id = dashboards_lines.id
                LEFT JOIN user_dashboards
                    ON user_dashboards.id = dashboards_lines.dashboard_id
                LEFT JOIN project_dashboards
                    ON project_dashboards.id = dashboards_lines.dashboard_id
                WHERE plugin_crosstracker_report.id = ?
                  AND widget.name = 'crosstrackersearch';";

        return $this->getDB()->row($sql, $content_id);
    }
}
