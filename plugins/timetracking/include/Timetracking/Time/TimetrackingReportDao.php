<?php
/**
 * Copyright Enalean (c) 2019. All rights reserved.
 *
 *  Tuleap and Enalean names and logos are registrated trademarks owned by
 *  Enalean SAS. All other trademarks or names are properties of their respective
 *  owners.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\Timetracking\Time;

use Tuleap\DB\DataAccessObject;

class TimetrackingReportDao extends DataAccessObject
{
    public function create()
    {
        $this->getDB()->run('INSERT INTO plugin_timetracking_overview_widget(id) VALUES (null)');
        return $this->getDB()->lastInsertId();
    }

    public function delete($report_id)
    {
        $sql = 'DELETE
                FROM plugin_timetracking_overview_widget
                WHERE id = ?';

        return $this->getDB()->run($sql, $report_id);
    }

    public function searchReportById(int $report_id)
    {
          $sql = 'SELECT id
                FROM plugin_timetracking_overview_widget
                WHERE id = ?';

        return $this->getDB()->single($sql, [$report_id]);
    }

    public function getReportTitleById(int $report_id)
    {
        $sql = 'SELECT widget_title
                FROM plugin_timetracking_overview_widget
                WHERE id = ?';

        return $this->getDB()->single($sql, [$report_id]);
    }

    public function setReportTitleById(string $widget_title, int $report_id)
    {
        $sql = 'UPDATE plugin_timetracking_overview_widget
                SET widget_title = ?
                WHERE id = ?';

        $this->getDB()->run($sql, $widget_title, $report_id);
    }

    public function searchTimetrackingWidgetByTimetrackingReportId(int $content_id)
    {
        $sql = "SELECT dashboard_id, user_id, project_dashboards.project_id
                  FROM plugin_timetracking_overview_widget
                INNER JOIN dashboards_lines_columns_widgets AS widget
                    ON plugin_timetracking_overview_widget.id = widget.content_id
                INNER JOIN dashboards_lines_columns
                    ON widget.column_id = dashboards_lines_columns.id
                INNER JOIN dashboards_lines
                    ON dashboards_lines_columns.line_id = dashboards_lines.id
                LEFT JOIN user_dashboards
                    ON user_dashboards.id = dashboards_lines.dashboard_id
                LEFT JOIN project_dashboards
                    ON project_dashboards.id = dashboards_lines.dashboard_id
                 WHERE plugin_timetracking_overview_widget.id = ?
                  AND widget.name = 'timetracking-overview'
                  AND dashboard_type = 'user'";

        return $this->getDB()->row($sql, $content_id);
    }


    public function searchReportTrackersById(int $report_id)
    {
        $sql = 'SELECT report_tracker.*
                  FROM plugin_timetracking_overview_widget AS report
                  INNER JOIN plugin_timetracking_overview_report_tracker AS report_tracker
                          ON report.id = report_tracker.report_id
                 WHERE report_id = ?';

        return $this->getDB()->run($sql, $report_id);
    }

    public function updateReport(int $report_id, array $trackers)
    {
        $this->getDB()->tryFlatTransaction(function () use ($report_id, $trackers): void {
            $this->getDB()->run(
                'DELETE FROM plugin_timetracking_overview_report_tracker WHERE report_id = ?',
                $report_id
            );
            $this->addTrackersToReport($trackers, $report_id);
        });
    }

    public function addTrackersToReport(array $trackers, int $report_id)
    {
        $data_to_insert = [];
        foreach ($trackers as $tracker) {
            $data_to_insert[] = ['report_id' => $report_id, 'tracker_id' => $tracker->getId()];
        }

        if (! empty($data_to_insert)) {
            $this->getDB()->insertMany('plugin_timetracking_overview_report_tracker', $data_to_insert);
        }
    }
}
