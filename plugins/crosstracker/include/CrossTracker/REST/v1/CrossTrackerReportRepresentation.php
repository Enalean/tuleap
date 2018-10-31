<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\REST\v1;

use Tuleap\REST\JsonCast;
use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\Tracker\REST\TrackerReference;

class CrossTrackerReportRepresentation
{
    const ROUTE = 'cross_tracker_reports';

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $uri;

    /** @var string */
    public $expert_query;

    /**
     * @var array {@type Tuleap\Tracker\REST\TrackerReference}
     */
    public $trackers;

    /**
     * @var array {@type Tuleap\Tracker\REST\TrackerReference}
     */
    public $invalid_trackers;

    public function build(CrossTrackerReport $report)
    {
        $this->id           = JsonCast::toInt($report->getId());
        $this->expert_query = $report->getExpertQuery();

        foreach ($report->getTrackers() as $tracker) {
            $tracker_reference = new TrackerReference();
            $tracker_reference->build($tracker);

            if (! $tracker->getProject()->isActive()) {
                $this->invalid_trackers[] = $tracker_reference;
            } else {
                $this->trackers[] = $tracker_reference;
            }
        }

        $this->uri = self::ROUTE . '/' . $this->id;
    }
}
