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

namespace Tuleap\CrossTracker;

use TrackerFactory;

class CrossTrackerReportFactory
{
    public function __construct(
        private readonly CrossTrackerReportDao $report_dao,
        private readonly TrackerFactory $tracker_factory,
    ) {
    }

    /**
     * @throws CrossTrackerReportNotFoundException
     */
    public function getById($id): CrossTrackerReport
    {
        $report_row = $this->report_dao->searchReportById($id);
        if (! $report_row) {
            throw new CrossTrackerReportNotFoundException();
        }
        $expert_query = $report_row['expert_query'];
        $expert_mode  = $report_row['expert_mode'];

        $report_trackers = [];
        $tracker_rows    = $this->report_dao->searchReportTrackersById($id);
        foreach ($tracker_rows as $row) {
            $tracker = $this->tracker_factory->getTrackerById($row['tracker_id']);
            if ($tracker !== null) {
                $report_trackers[] = $tracker;
            }
        }

        return new CrossTrackerReport($id, $expert_query, $report_trackers, $expert_mode);
    }
}
