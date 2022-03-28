<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\DocumentGeneration\CrossReport;

use Tracker_ReportFactory;

class CrossReportExportPropertiesFetcher
{
    public function __construct(private Tracker_ReportFactory $report_factory)
    {
    }

    public function fetchExportProperties(\Tracker $tracker, \Tracker_Report $tracker_report, \PFUser $current_user): CrossReportExportProperties
    {
        return new CrossReportExportProperties(
            $tracker->getName(),
            (int) $tracker_report->getId(),
            $this->getCurrentTrackerReportsProperties($tracker, $current_user),
        );
    }

    /**
     * @return CrossReportExportPropertiesReport[]
     */
    private function getCurrentTrackerReportsProperties(\Tracker $tracker, \PFUser $current_user): array
    {
        return array_values(
            array_map(
                static function (\Tracker_Report $report): CrossReportExportPropertiesReport {
                    return new CrossReportExportPropertiesReport(
                        (int) $report->getId(),
                        $report->getName(),
                        $report->isPublic()
                    );
                },
                $this->report_factory->getReportsByTrackerId(
                    $tracker->getId(),
                    (int) $current_user->getId(),
                )
            )
        );
    }
}
