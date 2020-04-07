<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;

class ScrumPlanningFilterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \PFUser
     */
    private $user;

    /**
     * @var \Planning
     */
    private $planning;

    /**
     * @var \PlanningFactory
     */
    private $planning_factory;

    /**
     * @var ScrumForMonoMilestoneChecker
     */
    private $mono_milestone_checker;

    /**
     * @var  ScrumPlanningFilter
     */
    private $scrum_planning_filter;

    protected function setUp(): void
    {
        $this->mono_milestone_checker = \Mockery::spy(\Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker::class);
        $this->planning_factory       = \Mockery::spy(\PlanningFactory::class);
        $this->planning               = \Mockery::spy(\Planning::class);
        $this->user                   = \Mockery::spy(\PFUser::class);

        $this->scrum_planning_filter  = new ScrumPlanningFilter(
            $this->mono_milestone_checker,
            $this->planning_factory
        );
    }

    public function testItRetrieveMonoMilestoneTrackerWhenScrumMonoMilestoneIsEnabled(): void
    {
        $this->mono_milestone_checker->shouldReceive('isMonoMilestoneEnabled')->with(101)->andReturns(true);
        $this->planning_factory->shouldReceive('getAvailableBacklogTrackers')->andReturns(array())->once();
        $this->planning_factory->shouldReceive('getPotentialPlanningTrackers')->andReturns(array())->once();

        $this->scrum_planning_filter->getPlanningTrackersFiltered(
            $this->planning,
            $this->user,
            101
        );

        $this->addToAssertionCount(1);
    }

    public function testItRetrieveMultiMilestoneTrackerWhenScrumMonoMilestoneIsDisabled(): void
    {
        $this->mono_milestone_checker->shouldReceive('isMonoMilestoneEnabled')->with(101)->andReturns(false);
        $this->planning_factory->shouldReceive('getAvailablePlanningTrackers')->once();
        $tracker = \Mockery::spy(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(888);
        $this->planning_factory->shouldReceive('getAvailablePlanningTrackers')->andReturns([$tracker]);

        $this->scrum_planning_filter->getPlanningTrackersFiltered(
            $this->planning,
            $this->user,
            101
        );

        $this->addToAssertionCount(1);
    }
}
