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

namespace Tuleap\CrossTracker\REST\v1;

use PFUser;
use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\REST\TrackerReference;

/**
 * @psalm-immutable
 */
class CrossTrackerReportRepresentation
{
    public const ROUTE        = 'cross_tracker_reports';
    public const MODE_DEFAULT = 'default';
    public const MODE_EXPERT  = 'expert';

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
     * @var self::MODE_*
     */
    public string $report_mode;

    /**
     * @param TrackerReference[] $trackers
     * @param TrackerReference[] $invalid_trackers
     * @param self::MODE_* $report_mode
     */
    private function __construct(int $id, string $expert_query, array $trackers, array $invalid_trackers, string $report_mode)
    {
        $this->id               = $id;
        $this->uri              = self::ROUTE . '/' . $this->id;
        $this->expert_query     = $expert_query;
        $this->trackers         = $trackers;
        $this->invalid_trackers = $invalid_trackers;
        $this->report_mode      = $report_mode;
    }

    public static function fromReport(CrossTrackerReport $report, PFUser $user): self
    {
        $trackers         = [];
        $invalid_trackers = [];

        foreach ($report->getTrackers() as $tracker) {
            if ($tracker->userCanView($user)) {
                $tracker_reference = TrackerReference::build($tracker);

                $trackers[] = $tracker_reference;
            }
        }

        foreach ($report->getInvalidTrackers() as $invalid_tracker) {
            $tracker_reference = TrackerReference::build($invalid_tracker);

            $invalid_trackers[] = $tracker_reference;
        }

        return new self(
            JsonCast::toInt($report->getId()),
            $report->getExpertQuery(),
            $trackers,
            $invalid_trackers,
            $report->isExpert() ? self::MODE_EXPERT : self::MODE_DEFAULT,
        );
    }
}
