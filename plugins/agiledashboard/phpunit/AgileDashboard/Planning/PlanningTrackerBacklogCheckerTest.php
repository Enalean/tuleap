<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types = 1);

namespace Tuleap\AgileDashboard\Planning;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning;
use PlanningFactory;
use Tracker;

class PlanningTrackerBacklogCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PlanningTrackerBacklogChecker
     */
    private $checker;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $tracker;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->planning_factory = Mockery::mock(PlanningFactory::class);

        $this->checker = new PlanningTrackerBacklogChecker(
            $this->planning_factory
        );

        $this->tracker = Mockery::mock(Tracker::class);
        $this->user    = Mockery::mock(PFUser::class);

        $this->tracker->shouldReceive('getGroupId')->andReturn('104');
        $this->tracker->shouldReceive('getId')->andReturn(187);
    }

    public function testItReturnsFalseIfNoRootPlanningInProject(): void
    {
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->andReturnNull();

        $this->assertFalse($this->checker->isTrackerBacklogOfProjectRootPlanning(
            $this->tracker,
            $this->user
        ));
    }

    public function testItReturnsFalseIfNotARootBacklogTrackerPlanning(): void
    {
        $planning = Mockery::mock(Planning::class)
            ->shouldReceive('getBacklogTrackersIds')
            ->andReturn([188, 189])
            ->getMock();

        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->andReturn($planning);

        $this->assertFalse($this->checker->isTrackerBacklogOfProjectRootPlanning(
            $this->tracker,
            $this->user
        ));
    }

    public function testItReturnsTrueIfTrackerIsARootBacklogTrackerPlanning(): void
    {
        $planning = Mockery::mock(Planning::class)
            ->shouldReceive('getBacklogTrackersIds')
            ->andReturn([188, 187])
            ->getMock();

        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->andReturn($planning);

        $this->assertTrue($this->checker->isTrackerBacklogOfProjectRootPlanning(
            $this->tracker,
            $this->user
        ));
    }

    public function testItReturnsFalseIfTrackerIsABacklogTrackerPlanning(): void
    {
        $planning = Mockery::mock(Planning::class)
            ->shouldReceive('getBacklogTrackersIds')
            ->andReturn([188, 189])
            ->getMock();

        $this->assertFalse($this->checker->isTrackerBacklogOfProjectPlanning(
            $planning,
            $this->tracker
        ));
    }

    public function testItReturnsTrueIfTrackerIsABacklogTrackerPlanning(): void
    {
        $planning = Mockery::mock(Planning::class)
            ->shouldReceive('getBacklogTrackersIds')
            ->andReturn([188, 187])
            ->getMock();

        $this->assertTrue($this->checker->isTrackerBacklogOfProjectPlanning(
            $planning,
            $this->tracker
        ));
    }
}
