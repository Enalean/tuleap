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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use TrackerFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\TestManagement\Config;

final class TestPlanTestDefinitionTrackerRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Config
     */
    private $testmanagement_config;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var TestPlanTestDefinitionTrackerRetriever
     */
    private $retriever;

    protected function setUp(): void
    {
        $this->testmanagement_config       = \Mockery::mock(Config::class);
        $this->tracker_factory             = \Mockery::mock(TrackerFactory::class);

        $this->retriever = new TestPlanTestDefinitionTrackerRetriever($this->testmanagement_config, $this->tracker_factory);
    }

    public function testCanRetrievesTestDefinitionsTracker(): void
    {
        $this->testmanagement_config->shouldReceive('getTestDefinitionTrackerId')->andReturn(146);
        $expected_tracker = \Mockery::mock(\Tracker::class);
        $this->tracker_factory->shouldReceive('getTrackerById')->andReturn($expected_tracker);

        $expected_tracker->shouldReceive('userCanView')->andReturn(true);

        $tracker = $this->retriever->getTestDefinitionTracker(\Mockery::mock(\Project::class), UserTestBuilder::aUser()->build());

        $this->assertSame($expected_tracker, $tracker);
    }

    public function testTestDefTrackerCannotBeRetrievedWhenTheUserCannotAccessIt(): void
    {
        $this->testmanagement_config->shouldReceive('getTestDefinitionTrackerId')->andReturn(146);
        $tracker = \Mockery::mock(\Tracker::class);
        $this->tracker_factory->shouldReceive('getTrackerById')->andReturn($tracker);

        $tracker->shouldReceive('userCanView')->andReturn(false);

        $this->assertNull(
            $this->retriever->getTestDefinitionTracker(\Mockery::mock(\Project::class), UserTestBuilder::aUser()->build())
        );
    }

    public function testTestDefTrackerCannotBeRetrievedNotSetInTheTestManagementConfig(): void
    {
        $this->testmanagement_config->shouldReceive('getTestDefinitionTrackerId')->andReturn(false);

        $this->assertNull(
            $this->retriever->getTestDefinitionTracker(\Mockery::mock(\Project::class), UserTestBuilder::aUser()->build())
        );
    }

    public function testTestDefTrackerCannotBeRetrievedWhenItDoesNotExist(): void
    {
        $this->testmanagement_config->shouldReceive('getTestDefinitionTrackerId')->andReturn(404);
        $this->tracker_factory->shouldReceive('getTrackerById')->andReturn(null);

        $this->assertNull(
            $this->retriever->getTestDefinitionTracker(\Mockery::mock(\Project::class), UserTestBuilder::aUser()->build())
        );
    }
}
