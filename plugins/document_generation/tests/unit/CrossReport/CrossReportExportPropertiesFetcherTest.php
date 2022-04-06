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

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\RetrieveCurrentlyUsedArtifactLinkTypesInTracker;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class CrossReportExportPropertiesFetcherTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\Tracker_ReportFactory
     */
    private $report_factory;
    private CrossReportExportPropertiesFetcher $fetcher;

    protected function setUp(): void
    {
        $this->report_factory = $this->createStub(\Tracker_ReportFactory::class);
        $this->fetcher        = new CrossReportExportPropertiesFetcher(
            $this->report_factory,
            new class implements RetrieveCurrentlyUsedArtifactLinkTypesInTracker {
                public function getAllCurrentlyUsedTypePresentersByTracker(\Tracker $tracker): array
                {
                    return [];
                }
            }
        );
    }

    public function testFetchesProperties(): void
    {
        $tracker           = TrackerTestBuilder::aTracker()->withName('Tracker Name')->build();
        $user              = UserTestBuilder::aUser()->withId(200)->build();
        $current_report_id = 101;
        $current_report    = self::buildReport($tracker, $current_report_id, 'Public', null);

        $this->report_factory->method('getReportsByTrackerId')->willReturn(
            [
                $current_report,
                self::buildReport(
                    $tracker,
                    102,
                    'Private',
                    $user
                ),
            ]
        );

        $props = $this->fetcher->fetchExportProperties(
            $tracker,
            $current_report,
            $user
        );

        self::assertEquals('Tracker Name', $props->current_tracker_name);
        self::assertEquals($current_report_id, $props->current_report_id);
        self::assertEquals(
            [
                new CrossReportExportPropertiesReport($current_report_id, 'Public', true),
                new CrossReportExportPropertiesReport(102, 'Private', false),
            ],
            $props->current_tracker_reports
        );
        self::assertEquals([], $props->current_tracker_artifact_link_types);
    }

    private static function buildReport(\Tracker $tracker, int $id, string $name, ?\PFUser $owned_by): \Tracker_Report
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
