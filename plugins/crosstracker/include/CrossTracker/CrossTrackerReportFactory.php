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

use Tuleap\CrossTracker\Report\RetrieveReport;
use Tuleap\CrossTracker\Report\SearchTrackersOfReport;
use Tuleap\Tracker\RetrieveTracker;

final readonly class CrossTrackerReportFactory
{
    public function __construct(
        private RetrieveReport $report_retriever,
        private SearchTrackersOfReport $trackers_searcher,
        private RetrieveTracker $tracker_factory,
    ) {
    }

    /**
     * @throws CrossTrackerReportNotFoundException
     */
    public function getById($id): CrossTrackerReport
    {
        $report_row = $this->report_retriever->searchReportById($id);
        if (! $report_row) {
            throw new CrossTrackerReportNotFoundException();
        }
        $expert_query = $report_row['expert_query'];
        $expert_mode  = $report_row['expert_mode'];
        if ($expert_mode) {
            return new CrossTrackerExpertReport($id, $expert_query);
        }

        $report_trackers = [];
        $tracker_ids     = $this->trackers_searcher->searchReportTrackersById($id);
        foreach ($tracker_ids as $tracker_id) {
            $tracker = $this->tracker_factory->getTrackerById($tracker_id);
            if ($tracker !== null) {
                $report_trackers[] = $tracker;
            }
        }

        return new CrossTrackerDefaultReport($id, $expert_query, $report_trackers);
    }
}
