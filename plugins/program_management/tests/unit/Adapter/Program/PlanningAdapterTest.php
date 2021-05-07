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

namespace Tuleap\ProgramManagement\Adapter\Program;

use Mockery;
use Planning;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PlanningHasNoProgramIncrementException;
use Tuleap\ProgramManagement\Domain\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class PlanningAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var PlanningAdapter
     */
    private $adapter;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PlanningFactory
     */
    private $planning_factory;

    protected function setUp(): void
    {
        $this->planning_factory = Mockery::mock(\PlanningFactory::class);
        $this->adapter          = new PlanningAdapter($this->planning_factory);
    }

    public function testThrowExceptionIfRootPlanningDoesNotExist(): void
    {
        $user       = UserTestBuilder::aUser()->build();
        $project_id = 101;
        $this->planning_factory->shouldReceive('getRootPlanning')->once()->andReturn(false);

        $this->expectException(TopPlanningNotFoundInProjectException::class);
        $this->adapter->getRootPlanning($user, $project_id);
    }

    public function testThrowExceptionIfRootPlanningHasNoPlanningTracker(): void
    {
        $planning   = new Planning(1, "test", 101, "backlog title", "plan title", []);
        $user       = UserTestBuilder::aUser()->build();
        $project_id = 101;
        $this->planning_factory->shouldReceive('getRootPlanning')->once()->andReturn($planning);

        $this->expectException(PlanningHasNoProgramIncrementException::class);
        $this->adapter->getRootPlanning($user, $project_id);
    }

    public function testItBuildARootPlanning(): void
    {
        $planning = new Planning(1, "test", 101, "backlog title", "plan title", []);
        $project  = new \Project(
            ['group_id' => 101, 'unix_group_name' => "project_name", 'group_name' => 'Public Name']
        );
        $tracker  = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();
        $planning->setPlanningTracker($tracker);

        $this->planning_factory->shouldReceive('getRootPlanning')->once()->andReturn($planning);

        $user       = UserTestBuilder::aUser()->build();
        $project_id = 101;

        $this->assertEquals($planning, $this->adapter->getRootPlanning($user, $project_id));
    }
}
