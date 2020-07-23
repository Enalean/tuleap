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

namespace Tuleap\CrossTracker\REST\v1;

use Tuleap\REST\JsonCast;
use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\Tracker\REST\TrackerReference;

/**
 * @psalm-immutable
 */
class CrossTrackerReportRepresentation
{
    public const ROUTE = 'cross_tracker_reports';

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
     * @var array {@type TrackerReference}
     */
    public $trackers = [];

    /**
     * @var array {@type TrackerReference}
     */
    public $invalid_trackers = [];

    /**
     * @param TrackerReference[] $trackers
     * @param TrackerReference[] $invalid_trackers
     */
    private function __construct(int $id, string $expert_query, array $trackers, array $invalid_trackers)
    {
        $this->id               = $id;
        $this->uri              = self::ROUTE . '/' . $this->id;
        $this->expert_query     = $expert_query;
        $this->trackers         = $trackers;
        $this->invalid_trackers = $invalid_trackers;
    }

    public static function fromReport(CrossTrackerReport $report): self
    {
        $trackers         = [];
        $invalid_trackers = [];

        foreach ($report->getTrackers() as $tracker) {
            $tracker_reference = TrackerReference::build($tracker);

            $trackers[] = $tracker_reference;
        }

        foreach ($report->getInvalidTrackers() as $invalid_tracker) {
            $tracker_reference = TrackerReference::build($invalid_tracker);

            $invalid_trackers[] = $tracker_reference;
        }

        return new self(
            JsonCast::toInt($report->getId()),
            $report->getExpertQuery(),
            $trackers,
            $invalid_trackers
        );
    }
}
