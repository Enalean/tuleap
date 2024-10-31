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

use ColinODell\PsrTestLogger\TestLogger;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\CrossTracker\CrossTrackerReportFactory;
use Tuleap\CrossTracker\Tests\Stub\Report\CloneReportStub;
use Tuleap\CrossTracker\Tests\Stub\Report\RetrieveReportStub;
use Tuleap\CrossTracker\Tests\Stub\Report\SearchTrackersOfReportStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;

final class ReportInheritanceHandlerTest extends TestCase
{
    private const TEMPLATE_REPORT_ID         = 90;
    private const CLONED_REPORT_ID           = 95;
    private const FIRST_TEMPLATE_TRACKER_ID  = 21;
    private const SECOND_TEMPLATE_TRACKER_ID = 26;
    private const FIRST_MAPPED_TRACKER_ID    = 98;
    private const SECOND_MAPPED_TRACKER_ID   = 149;
    private CloneReportStub $report_cloner;
    private SaveReportTrackers & MockObject $trackers_saver;
    private TestLogger $logger;
    /** @var array<int, int> $tracker_mapping */
    private array $tracker_mapping;
    private array $template_report;

    protected function setUp(): void
    {
        $this->report_cloner  = CloneReportStub::withClonedReportMap(
            [self::TEMPLATE_REPORT_ID => self::CLONED_REPORT_ID]
        );
        $this->trackers_saver = $this->createMock(SaveReportTrackers::class);
        $this->logger         = new TestLogger();

        $this->tracker_mapping = [
            self::FIRST_TEMPLATE_TRACKER_ID  => self::FIRST_MAPPED_TRACKER_ID,
            self::SECOND_TEMPLATE_TRACKER_ID => self::SECOND_MAPPED_TRACKER_ID,
        ];
        $this->template_report = ['id' => self::TEMPLATE_REPORT_ID, 'expert_query' => '@assigned_to = MYSELF()', 'expert_mode' => 0];
    }

    private function handle(): int
    {
        $template_project = ProjectTestBuilder::aProject()->withId(147)->build();

        $handler = new ReportInheritanceHandler(
            new CrossTrackerReportFactory(
                RetrieveReportStub::withReports($this->template_report),
                SearchTrackersOfReportStub::withTrackers(
                    ['report_id' => self::TEMPLATE_REPORT_ID, 'trackers' => [self::FIRST_TEMPLATE_TRACKER_ID, self::SECOND_TEMPLATE_TRACKER_ID]]
                ),
                RetrieveTrackerStub::withTrackers(
                    TrackerTestBuilder::aTracker()->withId(self::FIRST_TEMPLATE_TRACKER_ID)
                        ->withShortName('stories')
                        ->withProject($template_project)
                        ->build(),
                    TrackerTestBuilder::aTracker()->withId(self::SECOND_TEMPLATE_TRACKER_ID)
                        ->withShortName('bugs')
                        ->withProject($template_project)
                        ->build(),
                )
            ),
            $this->report_cloner,
            $this->trackers_saver,
            $this->logger
        );
        return $handler->handle(self::TEMPLATE_REPORT_ID, $this->tracker_mapping);
    }

    public function testItClonesDefaultReport(): void
    {
        $this->template_report = ['id' => self::TEMPLATE_REPORT_ID, 'expert_query' => '@assigned_to = MYSELF()', 'expert_mode' => 0];

        $this->trackers_saver->expects(self::once())
            ->method('addTrackersToReport')
            ->with(self::CLONED_REPORT_ID, [self::FIRST_MAPPED_TRACKER_ID, self::SECOND_MAPPED_TRACKER_ID]);

        $result = $this->handle();

        self::assertSame(self::CLONED_REPORT_ID, $result);
        self::assertSame(1, $this->report_cloner->getCallCount());
    }

    public function testItClonesExpertReport(): void
    {
        $this->template_report = [
            'id'           => self::TEMPLATE_REPORT_ID,
            'expert_query' => "SELECT @title FROM @tracker.name IN('stories', 'bugs') WHERE @assigned_to = MYSELF()",
            'expert_mode'  => 1,
        ];

        $this->trackers_saver->expects(self::never())->method('addTrackersToReport');

        $result = $this->handle();

        self::assertSame(self::CLONED_REPORT_ID, $result);
        self::assertSame(1, $this->report_cloner->getCallCount());
    }

    public function testItWritesLogsAndReturnsZeroToAvoidCrashingTheProjectCreationWhenTemplateReportIsNotFound(): void
    {
        $this->template_report = ['id' => 404, 'expert_query' => '', 'expert_mode' => 0];

        $this->trackers_saver->expects(self::never())->method('addTrackersToReport');

        $result = $this->handle();

        self::assertSame(0, $result);
        self::assertSame(0, $this->report_cloner->getCallCount());
        self::assertTrue($this->logger->hasError(
            sprintf('Could not find report #%d while duplicating Cross-Tracker Search widget', self::TEMPLATE_REPORT_ID)
        ));
    }

    public function testItSavesTrackersWithoutMappingAsIs(): void
    {
        $this->tracker_mapping = [];

        $this->trackers_saver->expects(self::once())
            ->method('addTrackersToReport')
            ->with(self::CLONED_REPORT_ID, [self::FIRST_TEMPLATE_TRACKER_ID, self::SECOND_TEMPLATE_TRACKER_ID]);

        $result = $this->handle();

        self::assertSame(self::CLONED_REPORT_ID, $result);
        self::assertSame(1, $this->report_cloner->getCallCount());
    }
}
