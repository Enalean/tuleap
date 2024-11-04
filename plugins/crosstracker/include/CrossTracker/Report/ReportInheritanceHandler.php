<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report;

use Psr\Log\LoggerInterface;
use Tuleap\CrossTracker\CrossTrackerReportFactory;
use Tuleap\CrossTracker\CrossTrackerReportNotFoundException;

final readonly class ReportInheritanceHandler
{
    public function __construct(
        private CrossTrackerReportFactory $report_factory,
        private CloneReport $report_cloner,
        private SaveReportTrackers $save_report_trackers,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<int, int> $tracker_mapping
     */
    public function handle(int $template_report_id, array $tracker_mapping): int
    {
        try {
            $template_report = $this->report_factory->getById($template_report_id);
        } catch (CrossTrackerReportNotFoundException) {
            $this->logger->error(
                sprintf('Could not find report #%d while duplicating Cross-Tracker Search widget', $template_report_id)
            );
            return 0;
        }

        $cloned_report_id = $this->report_cloner->cloneReport($template_report_id);
        if ($template_report->isExpert()) {
            return $cloned_report_id;
        }

        $mapped_tracker_ids = [];
        foreach ($template_report->getTrackers() as $template_tracker) {
            if (! isset($tracker_mapping[$template_tracker->getId()])) {
                // This tracker might be from another project than the template, we add it as-is.
                $mapped_tracker_ids[] = $template_tracker->getId();
                continue;
            }
            $mapped_tracker_ids[] = $tracker_mapping[$template_tracker->getId()];
        }
        $this->save_report_trackers->addTrackersToReport($cloned_report_id, $mapped_tracker_ids);
        return $cloned_report_id;
    }
}
