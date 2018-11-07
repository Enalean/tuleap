<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker;

use Tuleap\DB\DataAccessObject;

class CrossTrackerReportDao extends DataAccessObject
{
    public function searchReportById($report_id)
    {
        $sql = 'SELECT *
                FROM plugin_crosstracker_report
                WHERE id = ?';

        return $this->getDB()->row($sql, $report_id);
    }

    public function searchReportTrackersById($report_id)
    {
        $sql = 'SELECT report_tracker.*
                  FROM plugin_crosstracker_report AS report
                  INNER JOIN plugin_crosstracker_report_tracker AS report_tracker
                          ON report.id = report_tracker.report_id
                 WHERE report_id = ?';

        return $this->getDB()->run($sql, $report_id);
    }

    public function create()
    {
        $this->getDB()->run('INSERT INTO plugin_crosstracker_report(id) VALUES (null)');
        return $this->getDB()->lastInsertId();
    }

    public function updateReport($report_id, array $trackers, $expert_query)
    {
        $this->getDB()->beginTransaction();

        try {
            $this->getDB()->run('DELETE FROM plugin_crosstracker_report_tracker WHERE report_id = ?', $report_id);
            $this->addTrackersToReport($trackers, $report_id);
            $this->updateExpertQuery($report_id, $expert_query);
        } catch (\PDOException $ex) {
            $this->getDB()->rollBack();
            return;
        }

        $this->getDB()->commit();
    }

    private function updateExpertQuery($report_id, $expert_query)
    {
        $sql = 'REPLACE INTO plugin_crosstracker_report (id, expert_query) VALUES (?, ?)';
        $this->getDB()->run($sql, $report_id, $expert_query);
    }

    /**
     * @param array $trackers
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


    public function searchCrossTrackerWidgetByCrossTrackerReportId($content_id)
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
