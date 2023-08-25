<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning\RootPlanning;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Report\TrackerNotFoundException;

final class UpdateIsAllowedCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var UpdateIsAllowedChecker
     */
    private $checker;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\PlanningFactory
     */
    private $planning_factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|BacklogTrackerRemovalChecker
     */
    private $backlog_tracker_removal_checker;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\TrackerFactory
     */
    private $tracker_factory;

    protected function setUp(): void
    {
        $this->planning_factory                = M::mock(\PlanningFactory::class);
        $this->backlog_tracker_removal_checker = M::mock(BacklogTrackerRemovalChecker::class);
        $this->tracker_factory                 = M::mock(\TrackerFactory::class);
        $this->checker                         = new UpdateIsAllowedChecker(
            $this->planning_factory,
            $this->backlog_tracker_removal_checker,
            $this->tracker_factory
        );
    }

    public function testItReturnsIfNoRootPlanning(): void
    {
        $user     = UserTestBuilder::aUser()->build();
        $planning = PlanningBuilder::aPlanning(102)->withName('Not root planning')->build();
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->andReturnFalse();

        $this->checker->checkUpdateIsAllowed($planning, \PlanningParameters::fromArray([]), $user);
    }

    public function testItReturnsIfNotARootPlanning(): void
    {
        $user     = UserTestBuilder::aUser()->build();
        $planning = PlanningBuilder::aPlanning(102)->withId(15)->withName('Not root planning')->build();
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->andReturn(PlanningBuilder::aPlanning(102)->withId(1)->withName('Root planning')->build());

        $this->checker->checkUpdateIsAllowed($planning, \PlanningParameters::fromArray([]), $user);
    }

    public function testItThrowsWhenNewMilestoneTrackerIDIsNotAValidTrackerID(): void
    {
        $user     = UserTestBuilder::aUser()->build();
        $planning = PlanningBuilder::aPlanning(102)->withName('Not root planning')->build();
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->andReturn($planning);
        $this->backlog_tracker_removal_checker->shouldReceive('checkRemovedBacklogTrackersCanBeRemoved')
            ->once();
        $this->tracker_factory->shouldReceive('getTrackerById')
            ->once()
            ->andReturnNull();

        $this->expectException(TrackerNotFoundException::class);
        $this->checker->checkUpdateIsAllowed(
            $planning,
            \PlanningParameters::fromArray(['planning_tracker_id' => '404']),
            $user
        );
    }

    public function testItThrowsWhenNewMilestoneTrackerIDIsInAnotherProject(): void
    {
        $user     = UserTestBuilder::aUser()->build();
        $planning = PlanningBuilder::aPlanning(102)->withName('Not root planning')->build();
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->andReturn($planning);
        $this->backlog_tracker_removal_checker->shouldReceive('checkRemovedBacklogTrackersCanBeRemoved')
            ->once();
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getGroupId')->willReturn('103');
        $this->tracker_factory->shouldReceive('getTrackerById')
            ->once()
            ->andReturn($tracker);

        $this->expectException(TrackerNotFoundException::class);
        $this->checker->checkUpdateIsAllowed(
            $planning,
            \PlanningParameters::fromArray(['planning_tracker_id' => '404']),
            $user
        );
    }

    public function testItReturnsWhenUpdateIsAllowed(): void
    {
        $user     = UserTestBuilder::aUser()->build();
        $planning = PlanningBuilder::aPlanning(102)->withName('Not root planning')->build();
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->andReturn($planning);
        $this->backlog_tracker_removal_checker->shouldReceive('checkRemovedBacklogTrackersCanBeRemoved')
            ->once();
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getGroupId')->willReturn('102');
        $this->tracker_factory->shouldReceive('getTrackerById')
            ->once()
            ->with(86)
            ->andReturn($tracker);

        $this->checker->checkUpdateIsAllowed(
            $planning,
            \PlanningParameters::fromArray(['planning_tracker_id' => '86']),
            $user
        );
    }
}
