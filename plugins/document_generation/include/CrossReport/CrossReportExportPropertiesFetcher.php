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

class CrossReportExportPropertiesFetcher
{
    public function fetchExportProperties(\Tuleap\Tracker\Tracker $tracker, \Tracker_Report $tracker_report, \Tracker_Report_Renderer_Table $tracker_report_renderer_table): CrossReportExportProperties
    {
        return new CrossReportExportProperties(
            (int) $tracker->getGroupId(),
            $tracker->getId(),
            $tracker->getName(),
            (int) $tracker_report->getId(),
            $tracker_report->getName(),
            $tracker_report_renderer_table->getId()
        );
    }
}
