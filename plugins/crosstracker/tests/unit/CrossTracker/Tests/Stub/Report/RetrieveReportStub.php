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

namespace Tuleap\CrossTracker\Tests\Stub\Report;

final readonly class RetrieveReportStub implements \Tuleap\CrossTracker\Report\RetrieveReport
{
    /**
     * @param list<array{id: int, expert_query: string, expert_mode: 0|1 }> $reports
     */
    private function __construct(private array $reports)
    {
    }

    public function searchReportById(int $report_id): ?array
    {
        foreach ($this->reports as $row) {
            if ($row['id'] === $report_id) {
                return $row;
            }
        }
        return null;
    }

    /**
     * @param array{id: int, expert_query: string, expert_mode: 0|1 } $first_report
     * @param array{id: int, expert_query: string, expert_mode: 0|1 } ...$other_reports
     * @no-named-arguments
     */
    public static function withReports(array $first_report, array ...$other_reports): self
    {
        $reports = [$first_report, ...$other_reports];
        return new self($reports);
    }
}
