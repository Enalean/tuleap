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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Planning;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use PlanningDao;
use PlanningFactory;
use PlanningPermissionsManager;
use Tracker_Hierarchy;
use TrackerFactory;

class PlanningFactoryGetNonLastLevelPlanningsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningDao
     */
    private $planning_dao;
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
        $this->planning_dao           = Mockery::spy(PlanningDao::class);
        $this->tracker_factory        = Mockery::spy(TrackerFactory::class);
        $planning_permissions_manager = Mockery::spy(PlanningPermissionsManager::class);

        $this->partial_factory = Mockery::mock(
            PlanningFactory::class,
            [$this->planning_dao, $this->tracker_factory, $planning_permissions_manager]
        )->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testItReturnsAnEmptyArrayIfNoPlanningsExist(): void
    {
        $this->partial_factory->shouldReceive('getPlannings')->andReturn([]);

        $plannings = $this->partial_factory->getNonLastLevelPlannings(Mockery::mock(\PFUser::class), 14);

        $this->assertCount(0, $plannings);
    }

    public function testItDoesNotReturnLastLevelPlannings(): void
    {
        $planning_1 = Mockery::mock(\Planning::class);
        $planning_2 = Mockery::mock(\Planning::class);
        $planning_3 = Mockery::mock(\Planning::class);

        $planning_1->shouldReceive('getPlanningTrackerId')->andReturn(11);
        $planning_2->shouldReceive('getPlanningTrackerId')->andReturn(22);
        $planning_3->shouldReceive('getPlanningTrackerId')->andReturn(33);

        $this->partial_factory->shouldReceive('getPlannings')->andReturn([$planning_3, $planning_2, $planning_1]);

        $hierarchy = Mockery::mock(Tracker_Hierarchy::class);
        $hierarchy->shouldReceive('getLastLevelTrackerIds')->andReturn([11]);
        $hierarchy->shouldReceive('sortTrackerIds')->with([33, 22])->andReturn([22, 33]);
        $this->tracker_factory->shouldReceive('getHierarchy')->andReturn($hierarchy);

        $plannings = $this->partial_factory->getNonLastLevelPlannings(Mockery::mock(\PFUser::class), 14);

        $this->assertCount(2, $plannings);

        $first_planning  = $plannings[0];
        $second_planning = $plannings[1];

        $this->assertEquals(22, $first_planning->getPlanningTrackerId());
        $this->assertEquals(33, $second_planning->getPlanningTrackerId());
    }
}
