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
use Tuleap\CrossTracker\CrossTrackerDefaultReport;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\REST\TrackerReference;

/**
 * @psalm-immutable
 */
final readonly class CrossTrackerDefaultReportRepresentation
{
    public const ROUTE        = 'cross_tracker_reports';
    public const MODE_DEFAULT = 'default';
    public const MODE_EXPERT  = 'expert';

    /**
     * @param TrackerReference[] $trackers
     * @param TrackerReference[] $invalid_trackers
     * @param self::MODE_* $report_mode
     */
    private function __construct(
        public int $id,
        public string $uri,
        public string $expert_query,
        public array $trackers,
        public array $invalid_trackers,
        public string $report_mode,
    ) {
    }

    public static function fromReport(CrossTrackerDefaultReport $report, PFUser $user): self
    {
        $trackers         = [];
        $invalid_trackers = [];

        foreach ($report->getTrackers() as $tracker) {
            if ($tracker->userCanView($user)) {
                $trackers[] = TrackerReference::build($tracker);
            }
        }

        foreach ($report->getInvalidTrackers() as $invalid_tracker) {
            $invalid_trackers[] = TrackerReference::build($invalid_tracker);
        }

        $report_id = JsonCast::toInt($report->getId());
        return new self(
            $report_id,
            self::ROUTE . '/' . $report_id,
            $report->getExpertQuery(),
            $trackers,
            $invalid_trackers,
            $report->isExpert() ? self::MODE_EXPERT : self::MODE_DEFAULT,
        );
    }
}
