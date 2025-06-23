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

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CrossReportExportPropertiesFetcherTest extends TestCase
{
    private CrossReportExportPropertiesFetcher $fetcher;

    protected function setUp(): void
    {
        $this->fetcher = new CrossReportExportPropertiesFetcher();
    }

    public function testFetchesProperties(): void
    {
        $current_project_id = 999;

        $tracker             = TrackerTestBuilder::aTracker()
            ->withId(789)->withName('Tracker Name')
            ->withProject(ProjectTestBuilder::aProject()->withId($current_project_id)->build())
            ->build();
        $current_report_id   = 101;
        $current_report_name = 'Public';
        $current_report      = self::buildReport($tracker, $current_report_id, $current_report_name, null);

        $props = $this->fetcher->fetchExportProperties(
            $tracker,
            $current_report,
            new \Tracker_Report_Renderer_Table(136, $current_report, 'Renderer', '', 1, 10, false)
        );

        self::assertEquals($current_project_id, $props->current_project_id);
        self::assertEquals(789, $props->current_tracker_id);
        self::assertEquals('Tracker Name', $props->current_tracker_name);
        self::assertEquals($current_report_id, $props->current_report_id);
        self::assertEquals($current_report_name, $props->current_report_name);
        self::assertEquals(136, $props->current_renderer_id);
    }

    private static function buildReport(\Tuleap\Tracker\Tracker $tracker, int $id, string $name, ?\PFUser $owned_by): \Tracker_Report
    {
        $owned_by_id = $owned_by?->getId();
        return new \Tracker_Report(
            $id,
            $name,
            'Description',
            0,
            0,
            $owned_by_id === null ? null : (int) $owned_by_id,
            false,
            $tracker->getId(),
            true,
            false,
            '',
            null,
            0
        );
    }
}
