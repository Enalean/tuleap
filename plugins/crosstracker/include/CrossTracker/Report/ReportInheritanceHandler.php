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
        private CloneWidget $report_cloner,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(int $template_report_id): int
    {
        try {
            $this->report_factory->getById($template_report_id);
        } catch (CrossTrackerReportNotFoundException) {
            $this->logger->error(
                sprintf('Could not find report #%d while duplicating Cross-Tracker Search widget', $template_report_id)
            );
            return 0;
        }

        return $this->report_cloner->cloneWidget($template_report_id);
    }
}
