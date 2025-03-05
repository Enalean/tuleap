<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\TestPlan;

use TrackerFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\TestManagement\Config;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TestPlanTestDefinitionTrackerRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TestPlanTestDefinitionTrackerRetriever $retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Config
     */
    private $testmanagement_config;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TrackerFactory
     */
    private $tracker_factory;

    protected function setUp(): void
    {
        $this->testmanagement_config = $this->createMock(Config::class);
        $this->tracker_factory       = $this->createMock(TrackerFactory::class);

        $this->retriever = new TestPlanTestDefinitionTrackerRetriever($this->testmanagement_config, $this->tracker_factory);
    }

    public function testCanRetrievesTestDefinitionsTracker(): void
    {
        $this->testmanagement_config->method('getTestDefinitionTrackerId')->willReturn(146);
        $expected_tracker = $this->createMock(\Tracker::class);
        $this->tracker_factory->method('getTrackerById')->willReturn($expected_tracker);

        $expected_tracker->method('userCanView')->willReturn(true);

        $tracker = $this->retriever->getTestDefinitionTracker($this->createMock(\Project::class), UserTestBuilder::aUser()->build());

        self::assertSame($expected_tracker, $tracker);
    }

    public function testTestDefTrackerCannotBeRetrievedWhenTheUserCannotAccessIt(): void
    {
        $this->testmanagement_config->method('getTestDefinitionTrackerId')->willReturn(146);
        $tracker = $this->createMock(\Tracker::class);
        $this->tracker_factory->method('getTrackerById')->willReturn($tracker);

        $tracker->method('userCanView')->willReturn(false);

        self::assertNull(
            $this->retriever->getTestDefinitionTracker($this->createMock(\Project::class), UserTestBuilder::aUser()->build())
        );
    }

    public function testTestDefTrackerCannotBeRetrievedNotSetInTheTestManagementConfig(): void
    {
        $this->testmanagement_config->method('getTestDefinitionTrackerId')->willReturn(false);

        self::assertNull(
            $this->retriever->getTestDefinitionTracker($this->createMock(\Project::class), UserTestBuilder::aUser()->build())
        );
    }

    public function testTestDefTrackerCannotBeRetrievedWhenItDoesNotExist(): void
    {
        $this->testmanagement_config->method('getTestDefinitionTrackerId')->willReturn(404);
        $this->tracker_factory->method('getTrackerById')->willReturn(null);

        self::assertNull(
            $this->retriever->getTestDefinitionTracker($this->createMock(\Project::class), UserTestBuilder::aUser()->build())
        );
    }
}
