<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
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
 */

declare(strict_types=1);

namespace Tuleap\CrossTracker\REST\v1;

use Tuleap\CrossTracker\CrossTrackerExpertReport;
use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 */
final readonly class CrossTrackerExpertReportRepresentation
{
    private function __construct(
        public int $id,
        public string $uri,
        public string $expert_query,
        public string $title,
        public string $description,
    ) {
    }

    public static function fromReport(CrossTrackerExpertReport $report): self
    {
        $report_id = JsonCast::toInt($report->getId());
        return new self(
            $report_id,
            CrossTrackerReportsResource::ROUTE . '/' . $report_id,
            $report->getQuery(),
            $report->getTitle(),
            $report->getDescription(),
        );
    }
}
