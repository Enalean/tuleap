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
use Tuleap\CrossTracker\SearchCrossTrackerWidgetStub;
use Tuleap\CrossTracker\Tests\Stub\Report\CloneWidgetStub;
use Tuleap\CrossTracker\Widget\SearchCrossTrackerWidget;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ReportInheritanceHandlerTest extends TestCase
{
    private const TEMPLATE_REPORT_ID = 90;
    private const CLONED_REPORT_ID   = 95;
    private SearchCrossTrackerWidget $widget_dao;
    private CloneWidgetStub $report_cloner;
    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->widget_dao    = SearchCrossTrackerWidgetStub::withoutExistingWidget();
        $this->report_cloner = CloneWidgetStub::withClonedReportMap(
            [self::TEMPLATE_REPORT_ID => self::CLONED_REPORT_ID]
        );
        $this->logger        = new TestLogger();
    }

    private function handle(int $id): int
    {
        $handler = new ReportInheritanceHandler(
            $this->widget_dao,
            $this->report_cloner,
            $this->logger
        );
        return $handler->handle($id);
    }

    public function testItClonesExpertReport(): void
    {
        $this->widget_dao = SearchCrossTrackerWidgetStub::withExistingWidget([]);

        $result = $this->handle(self::TEMPLATE_REPORT_ID);

        self::assertSame(self::CLONED_REPORT_ID, $result);
        self::assertSame(1, $this->report_cloner->getCallCount());
    }

    public function testItWritesLogsAndReturnsZeroToAvoidCrashingTheProjectCreationWhenTemplateReportIsNotFound(): void
    {
        $result = $this->handle(404);

        self::assertSame(0, $result);
        self::assertSame(0, $this->report_cloner->getCallCount());
        self::assertTrue($this->logger->hasError('Could not find report #404 while duplicating Cross-Tracker Search widget'));
    }
}
