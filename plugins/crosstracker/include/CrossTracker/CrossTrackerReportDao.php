<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use DataAccessObject;
use DataAccessQueryException;

class CrossTrackerReportDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->enableExceptionsOnError();
    }

    public function searchReportById($report_id)
    {
        $report_id = $this->da->escapeInt($report_id);

        $sql = "SELECT *
                FROM plugin_crosstracker_report
                WHERE id = $report_id";

        return $this->retrieveFirstRow($sql);
    }

    public function searchReportTrackersById($report_id)
    {
        $report_id = $this->da->escapeInt($report_id);

        $sql = "SELECT report_tracker.*
                  FROM plugin_crosstracker_report AS report
                  INNER JOIN plugin_crosstracker_report_tracker AS report_tracker
                          ON report.id = report_tracker.report_id
                 WHERE report_id = $report_id";

        return $this->retrieve($sql);
    }

    public function create()
    {
        $sql = "INSERT INTO plugin_crosstracker_report(id)
            VALUES (null)";

        return $this->updateAndGetLastId($sql);
    }

    /**
     * @param int $report_id
     * @param \Tracker[] $trackers
     */
    public function updateReport($report_id, array $trackers)
    {
        $this->da->startTransaction();

        $report_id = $this->da->escapeInt($report_id);

        try {
            $sql = "DELETE FROM plugin_crosstracker_report_tracker WHERE report_id = $report_id";
            $this->update($sql);

            $this->addTrackersToReport($trackers, $report_id);
        } catch (DataAccessQueryException $e) {
            $this->rollBack();

            return;
        }

        $this->da->commit();
    }


    /**
     * @param array $trackers
     * @param       $report_id
     */
    public function addTrackersToReport(array $trackers, $report_id)
    {
        $report_id = $this->da->escapeInt($report_id);

        $sql_value = array();
        foreach ($trackers as $tracker) {
            $tracker_id  = $this->da->escapeInt($tracker->getId());
            $sql_value[] = "($report_id, $tracker_id)";
        }

        if (count($sql_value) > 0) {
            $sql = "INSERT INTO plugin_crosstracker_report_tracker(report_id, tracker_id) VALUES " .
                implode(',', $sql_value);

            $this->update($sql);
        }
    }

    public function deleteTrackersByGroupId($group_id)
    {
        $group_id = $this->da->escapeInt($group_id);

        $sql = "DELETE report.* FROM plugin_crosstracker_report_tracker report
                  INNER JOIN tracker ON report.tracker_id = tracker.id
                WHERE tracker.group_id = $group_id";

        $this->update($sql);
    }

    public function searchTrackersIdUsedByCrossTrackerByProjectId($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT tracker.id
                FROM plugin_crosstracker_report_tracker AS report
                INNER JOIN tracker ON report.tracker_id = tracker.id
                WHERE tracker.group_id = $project_id";

        return $this->retrieve($sql);
    }

    public function delete($report_id)
    {
        $report_id = $this->da->escapeInt($report_id);

        $sql = "DELETE report.*, tracker_report.*
                FROM plugin_crosstracker_report AS report
                  LEFT JOIN plugin_crosstracker_report_tracker AS tracker_report
                    ON (report.id = tracker_report.report_id)
                  WHERE report.id = $report_id;";

        $this->update($sql);
    }


    public function searchCrossTrackerWidgetByCrossTrackerReportId($content_id)
    {
        $content_id = $this->da->escapeInt($content_id);

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
                WHERE plugin_crosstracker_report.id = $content_id
                  AND widget.name = 'crosstrackersearch';";

        return $this->retrieveFirstRow($sql);
    }
}
