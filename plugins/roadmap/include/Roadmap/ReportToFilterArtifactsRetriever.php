<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap;

final class ReportToFilterArtifactsRetriever implements RetrieveReportToFilterArtifacts
{
    public function __construct(private readonly FilterReportDao $dao, private readonly \Tracker_ReportFactory $report_factory)
    {
    }

    public function getReportToFilterArtifacts(int $widget_id, \PFUser $user): ?\Tracker_Report
    {
        $report_id = $this->dao->getReportIdToFilterArtifacts($widget_id);
        if (! $report_id) {
            return null;
        }

        return $this->report_factory->getReportById($report_id, $user->getId(), false);
    }
}
