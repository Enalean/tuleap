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

/**
 * @psalm-immutable
 */
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
     * @var TrackerReference[] {@type TrackerReference}
     */
    public $trackers = [];

    /**
     * @var TrackerReference[] {@type TrackerReference}
     */
    public $invalid_trackers = [];

    /**
     * @param TrackerReference[] $trackers
     * @param TrackerReference[] $invalid_trackers
     */
    private function __construct(int $id, array $trackers, array $invalid_trackers)
    {
        $this->id               = $id;
        $this->uri              = self::NAME . '/' . $this->id;
        $this->trackers         = $trackers;
        $this->invalid_trackers = $invalid_trackers;
    }

    public static function fromReport(TimetrackingReport $report): self
    {
        $trackers = [];
        foreach ($report->getTrackers() as $tracker) {
            $trackers[] = TrackerReference::build($tracker);
        }

        $invalid_trackers = [];
        foreach ($report->getInvalidTrackers() as $invalid_tracker) {
            $invalid_trackers[] = TrackerReference::build($invalid_tracker);
        }

        return new self(
            JsonCast::toInt($report->getId()),
            $trackers,
            $invalid_trackers,
        );
    }
}
