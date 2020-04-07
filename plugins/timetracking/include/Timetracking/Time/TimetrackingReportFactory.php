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

use Tuleap\Timetracking\Exceptions\TimetrackingReportNotFoundException;

class TimetrackingReportFactory
{
    /**
     * @var TimetrackingReportDao
     */
    private $report_dao;

    /**
     * @var \TrackerFactory
     */
    private $tracker_factory;

    public function __construct(TimetrackingReportDao $report_dao, \TrackerFactory $tracker_factory)
    {
        $this->report_dao      = $report_dao;
        $this->tracker_factory = $tracker_factory;
    }

    /**
     * @throws TimetrackingReportNotFoundException
     */
    public function getReportById(int $id): TimetrackingReport
    {
        $report_id = $this->report_dao->searchReportById($id);

        if (! $report_id) {
            throw new TimetrackingReportNotFoundException();
        }

        $report_trackers = array();
        $tracker_rows    = $this->report_dao->searchReportTrackersById($id);

        foreach ($tracker_rows as $row) {
            $tracker = $this->tracker_factory->getTrackerById($row['tracker_id']);
            if ($tracker !== null) {
                $report_trackers[] = $tracker;
            }
        }
        return new TimetrackingReport($report_id, $report_trackers);
    }
}
