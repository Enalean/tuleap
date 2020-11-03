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

namespace Tuleap\ScaledAgile\Adapter;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use TrackerFactory;
use Tuleap\ScaledAgile\TrackerData;
use Tuleap\ScaledAgile\TrackerNotFoundException;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TrackerDataAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TrackerDataAdapter
     */
    private $adapter;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;

    protected function setUp(): void
    {
        $this->tracker_factory = \Mockery::mock(TrackerFactory::class);
        $this->adapter = new TrackerDataAdapter($this->tracker_factory);
    }

    public function testItBuildsTrackerDataByTrackerId(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        $this->tracker_factory->shouldReceive('getTrackerById')->with(1)->once()->andReturn($tracker);

        $tracker_data = new TrackerData($tracker);
        $this->assertEquals($tracker_data, $this->adapter->buildByTrackerID(1));
    }

    public function testItThrowsAnExceptionWhenTrackerIsNotFound(): void
    {
        $this->tracker_factory->shouldReceive('getTrackerById')->with(1)->once()->andReturnNull();

        $this->expectException(TrackerNotFoundException::class);
        $this->adapter->buildByTrackerID(1);
    }
}
