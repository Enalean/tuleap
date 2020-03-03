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
use PlanningDao;
use PlanningFactory;
use PlanningPermissionsManager;
use TrackerFactory;

final class PlanningFactoryTestGetAvailablePlanningTrackersTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var Mockery\Mock | PlanningFactory
     */
    private $partial_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;

    protected function setUp(): void
    {
        $planning_dao                       = Mockery::spy(PlanningDao::class);
        $this->tracker_factory              = Mockery::spy(TrackerFactory::class);
        $planning_permissions_manager = Mockery::spy(PlanningPermissionsManager::class);

        $this->partial_factory = Mockery::mock(
            PlanningFactory::class,
            [$planning_dao, $this->tracker_factory, $planning_permissions_manager]
        )->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testItRetrievesAvailablePlanningTrackersIncludingTheCurrentPlanningTracker(): void
    {
        $group_id = 789;

        $this->partial_factory->shouldReceive('getPotentialPlanningTrackerIds')->andReturn([1, 2, 3]);
        $this->partial_factory->shouldReceive('getPlanningTrackerIdsByGroupId')->andReturn([1, 3]);

        $releases_tracker = Mockery::mock(\Tracker::class);

        $this->tracker_factory->shouldReceive('getTrackerById')->with(2)->andReturn($releases_tracker)->once();

        $actual_trackers = $this->partial_factory->getAvailablePlanningTrackers(
            Mockery::mock(PFUser::class),
            $group_id
        );

        $this->assertEquals([$releases_tracker], $actual_trackers);
    }
}
