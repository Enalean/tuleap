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

namespace Tuleap\CrossTracker\Widget;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\CrossTracker\Tests\Stub\Widget\CloneWidgetStub;
use Tuleap\CrossTracker\Tests\Stub\Widget\SearchCrossTrackerWidgetStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WidgetInheritanceHandlerTest extends TestCase
{
    private const TEMPLATE_WIDGET_ID = 90;
    private const CLONED_WIDGET_ID   = 95;
    private SearchCrossTrackerWidget $widget_dao;
    private CloneWidgetStub $widget_cloner;
    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->widget_dao    = SearchCrossTrackerWidgetStub::withoutExistingWidget();
        $this->widget_cloner = CloneWidgetStub::withClonedWidgetMap(
            [self::TEMPLATE_WIDGET_ID => self::CLONED_WIDGET_ID]
        );
        $this->logger        = new TestLogger();
    }

    private function handle(int $id): int
    {
        $handler = new WidgetInheritanceHandler(
            $this->widget_dao,
            $this->widget_cloner,
            $this->logger
        );
        return $handler->handle($id);
    }

    public function testItClonesWidget(): void
    {
        $this->widget_dao = SearchCrossTrackerWidgetStub::withExistingWidget([]);

        $result = $this->handle(self::TEMPLATE_WIDGET_ID);

        self::assertSame(self::CLONED_WIDGET_ID, $result);
        self::assertSame(1, $this->widget_cloner->getCallCount());
    }

    public function testItWritesLogsAndReturnsZeroToAvoidCrashingTheProjectCreationWhenTemplateWidgetIsNotFound(): void
    {
        $result = $this->handle(404);

        self::assertSame(0, $result);
        self::assertSame(0, $this->widget_cloner->getCallCount());
        self::assertTrue($this->logger->hasError('Could not find widget #404 while duplicating Cross-Tracker Search widget'));
    }
}
