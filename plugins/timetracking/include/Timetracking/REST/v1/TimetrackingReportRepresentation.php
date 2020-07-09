<?php
/**
 * Copyright Enalean (c) 2019-Present. All rights reserved.
 *
 *  Tuleap and Enalean names and logos are registered trademarks owned by
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

namespace Tuleap\Timetracking\REST\v1;

use Tuleap\REST\JsonCast;
use Tuleap\Timetracking\Time\TimetrackingReport;
use Tuleap\Tracker\REST\TrackerReference;

class TimetrackingReportRepresentation
{
    public const NAME = "timetracking_reports";
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
    public $trackers = [];

    /**
     * @var array {@type Tuleap\Tracker\REST\TrackerReference}
     */
    public $invalid_trackers = [];

    public function build(TimetrackingReport $report)
    {
        $this->id = JsonCast::toInt($report->getId());
        foreach ($report->getTrackers() as $tracker) {
            $tracker_reference = TrackerReference::build($tracker);

            $this->trackers[] = $tracker_reference;
        }

        foreach ($report->getInvalidTrackers() as $invalid_tracker) {
            $tracker_reference = TrackerReference::build($invalid_tracker);

            $this->invalid_trackers[] = $tracker_reference;
        }

        $this->uri = self::NAME . '/' . $this->id;
    }
}
