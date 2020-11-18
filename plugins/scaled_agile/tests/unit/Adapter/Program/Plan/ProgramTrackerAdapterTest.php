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

namespace Tuleap\ScaledAgile\Adapter\Program\Plan;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ScaledAgile\Program\Plan\ProgramIncrementTracker;
use Tuleap\ScaledAgile\Program\Plan\ProgramPlannableTracker;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramTrackerAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ProgramTrackerAdapter
     */
    private $adapter;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\TrackerFactory
     */
    private $tracker_factory;

    protected function setUp(): void
    {
        $this->tracker_factory = \Mockery::mock(\TrackerFactory::class);
        $this->adapter         = new ProgramTrackerAdapter($this->tracker_factory);
    }

    public function testItThrowsAnExceptionWhenTrackerIsNotFound(): void
    {
        $tracker_id = 1;
        $project_id = 101;
        $this->tracker_factory->shouldReceive('getTrackerById')->with($tracker_id)->once()->andReturnNull();

        $this->expectException(PlanTrackerNotFoundException::class);

        $this->adapter->buildProgramIncrementTracker($tracker_id, $project_id);
    }

    public function testItThrowsAnExceptionWhenTrackerDoesNotBelongToProject(): void
    {
        $tracker_id = 1;
        $project_id = 101;
        $tracker    = TrackerTestBuilder::aTracker()->withId($tracker_id)->build();
        $this->tracker_factory->shouldReceive('getTrackerById')->with($tracker_id)->once()->andReturn($tracker);

        $this->expectException(PlanTrackerDoesNotBelongToProjectException::class);

        $this->adapter->buildProgramIncrementTracker($tracker_id, $project_id);
    }

    public function testItBuildAProgramIncrement(): void
    {
        $tracker_id = 1;
        $project_id = 101;
        $project    = new \Project(['group_id' => $project_id]);
        $tracker    = TrackerTestBuilder::aTracker()->withId($tracker_id)->withProject($project)->build();
        $this->tracker_factory->shouldReceive('getTrackerById')->with($tracker_id)->once()->andReturn($tracker);

        $expected = new ProgramIncrementTracker($tracker_id);

        $this->assertEquals($expected, $this->adapter->buildProgramIncrementTracker($tracker_id, $project_id));
    }

    public function testItThrowsAnExceptionWhenTrackerListIsEmpty(): void
    {
        $project_id = 101;

        $this->expectException(PlannableTrackerCannotBeEmptyException::class);
        $this->adapter->buildPlannableTrackers([], $project_id);
    }

    public function testItBuildPlannableTrackers(): void
    {
        $tracker_id = 1;
        $project_id = 101;
        $project    = new \Project(['group_id' => $project_id]);
        $tracker    = TrackerTestBuilder::aTracker()->withId($tracker_id)->withProject($project)->build();
        $this->tracker_factory->shouldReceive('getTrackerById')->with($tracker_id)->once()->andReturn($tracker);

        $expected = [new ProgramPlannableTracker($tracker_id)];
        $this->assertEquals($expected, $this->adapter->buildPlannableTrackers([$tracker_id], $project_id));
    }
}
