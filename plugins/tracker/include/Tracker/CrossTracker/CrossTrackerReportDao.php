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

namespace Tuleap\Tracker\CrossTracker;

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
                FROM plugin_tracker_cross_tracker_report
                WHERE id = $report_id";

        return $this->retrieveFirstRow($sql);
    }

    public function searchReportTrackersById($report_id)
    {
        $report_id = $this->da->escapeInt($report_id);

        $sql = "SELECT report_tracker.*
                  FROM plugin_tracker_cross_tracker_report AS report
                  INNER JOIN plugin_tracker_cross_tracker_report_tracker AS report_tracker
                          ON report.id = report_tracker.report_id
                 WHERE report_id = $report_id";

        return $this->retrieve($sql);
    }

    /**
     * @param \Tracker[] $trackers
     */
    public function save(array $trackers)
    {
        $this->da->startTransaction();

        try {
            $sql = "INSERT INTO plugin_tracker_cross_tracker_report(id)
            VALUES (null)";

            $report_id = $this->updateAndGetLastId($sql);
            $this->addTrackersToReport($trackers, $report_id);
        } catch (DataAccessQueryException $e) {
            $this->rollBack();

            return false;
        }

        $this->da->commit();
    }

    /**
     * @param array $trackers
     * @param       $report_id
     */
    private function addTrackersToReport(array $trackers, $report_id)
    {
        $report_id = $this->da->escapeInt($report_id);

        $sql_value = array();
        foreach ($trackers as $tracker) {
            $tracker_id  = $this->da->escapeInt($tracker->getId());
            $sql_value[] = "($report_id, $tracker_id)";
        }

        $sql = "INSERT INTO plugin_tracker_cross_tracker_report_tracker(report_id, tracker_id) VALUES " .
            implode(',', $sql_value);

        $this->update($sql);
    }
}
