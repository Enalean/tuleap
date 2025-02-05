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
use Tuleap\CrossTracker\CrossTrackerReportFactory;
use Tuleap\CrossTracker\Tests\Stub\Report\CloneWidgetStub;
use Tuleap\CrossTracker\Tests\Stub\Report\RetrieveReportStub;
use Tuleap\Test\PHPUnit\TestCase;

final class ReportInheritanceHandlerTest extends TestCase
{
    private const TEMPLATE_REPORT_ID = 90;
    private const CLONED_REPORT_ID   = 95;
    private CloneWidgetStub $report_cloner;
    private TestLogger $logger;
    private array $template_report;

    protected function setUp(): void
    {
        $this->report_cloner   = CloneWidgetStub::withClonedReportMap(
            [self::TEMPLATE_REPORT_ID => self::CLONED_REPORT_ID]
        );
        $this->logger          = new TestLogger();
        $this->template_report = [];
    }

    private function handle(): int
    {
        $handler = new ReportInheritanceHandler(
            new CrossTrackerReportFactory(
                RetrieveReportStub::withReports($this->template_report)
            ),
            $this->report_cloner,
            $this->logger
        );
        return $handler->handle(self::TEMPLATE_REPORT_ID);
    }

    public function testItClonesExpertReport(): void
    {
        $this->template_report = [
            'id'          => self::TEMPLATE_REPORT_ID,
            'query'       => "SELECT @title FROM @tracker.name IN('stories', 'bugs') WHERE @assigned_to = MYSELF()",
            'title'       => '',
            'description' => '',
        ];

        $result = $this->handle();

        self::assertSame(self::CLONED_REPORT_ID, $result);
        self::assertSame(1, $this->report_cloner->getCallCount());
    }

    public function testItWritesLogsAndReturnsZeroToAvoidCrashingTheProjectCreationWhenTemplateReportIsNotFound(): void
    {
        $this->template_report = ['id' => 404, 'query' => '', 'title' => '', 'description' => ''];

        $result = $this->handle();

        self::assertSame(0, $result);
        self::assertSame(0, $this->report_cloner->getCallCount());
        self::assertTrue($this->logger->hasError(
            sprintf('Could not find report #%d while duplicating Cross-Tracker Search widget', self::TEMPLATE_REPORT_ID)
        ));
    }
}
