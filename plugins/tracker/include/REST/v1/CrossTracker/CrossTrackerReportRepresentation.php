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

namespace Tuleap\Tracker\REST\v1\CrossTracker;

use Tuleap\REST\JsonCast;
use Tuleap\Tracker\CrossTracker\CrossTrackerReport;
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

    /**
     * @var array {@type Tuleap\Tracker\REST\TrackerReference}
     */
    public $trackers;

    public function build(CrossTrackerReport $report)
    {
        $this->id = JsonCast::toInt($report->getId());

        foreach ($report->getTrackers() as $tracker) {
            $tracker_reference = new TrackerReference();
            $tracker_reference->build($tracker);
            $this->trackers[] = $tracker_reference;
        }

        $this->uri = self::ROUTE . '/' . $this->id;
    }
}
