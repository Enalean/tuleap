<?php
/*
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\MultiProjectBacklog\Aggregator\ContributorProjectsCollection;
use Tuleap\Test\Builders\UserTestBuilder;

final class MilestoneTrackerCollectionBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var M\LegacyMockInterface|M\MockInterface|\PlanningFactory
     */
    private $planning_factory;
    /**
     * @var MilestoneTrackerCollectionBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->planning_factory = M::mock(\PlanningFactory::class);
        $this->builder          = new MilestoneTrackerCollectionBuilder($this->planning_factory);
    }

    public function testBuildFromContributorProjects(): void
    {
        $first_project  = new \Project(['group_id' => '103']);
        $second_project = new \Project(['group_id' => '123']);
        $projects       = new ContributorProjectsCollection([$first_project, $second_project]);

        $user = UserTestBuilder::aUser()->build();

        $first_planning          = new \Planning(7, 'First Contributor Root Planning', 103, 'Irrelevant', 'Irrelevant');
        $first_tracker_id        = 1024;
        $first_milestone_tracker = M::mock(\Tracker::class);
        $first_milestone_tracker->shouldReceive('getId')->andReturn($first_tracker_id);
        $first_planning->setPlanningTracker($first_milestone_tracker);
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->with($user, 103)
            ->andReturn($first_planning);

        $second_planning          = new \Planning(9, 'Second Contributor Root Planning', 123, 'Irrelevant', 'Irrelevant');
        $second_tracker_id        = 2048;
        $second_milestone_tracker = M::mock(\Tracker::class);
        $second_milestone_tracker->shouldReceive('getId')->andReturn($second_tracker_id);
        $second_planning->setPlanningTracker($second_milestone_tracker);
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->with($user, 123)
            ->andReturn($second_planning);

        $trackers = $this->builder->buildFromContributorProjects($projects, $user);
        $this->assertContains($first_tracker_id, $trackers->getTrackerIds());
        $this->assertContains($second_tracker_id, $trackers->getTrackerIds());
    }

    public function testBuildFromContributorProjectsThrowsWhenContributorProjectHasNoRootPlanning(): void
    {
        $first_project  = new \Project(['group_id' => '103']);
        $second_project = new \Project(['group_id' => '123']);
        $projects       = new ContributorProjectsCollection([$first_project, $second_project]);

        $user = UserTestBuilder::aUser()->build();

        $this->planning_factory->shouldReceive('getRootPlanning')
            ->andReturnNull();

        $this->expectException(MissingRootPlanningException::class);
        $this->builder->buildFromContributorProjects($projects, $user);
    }

    public function testBuildFromContributorProjectsThrowsWhenPlanningIsMalformedAndHasNoMilestoneTracker(): void
    {
        $first_project  = new \Project(['group_id' => '103']);
        $second_project = new \Project(['group_id' => '123']);
        $projects       = new ContributorProjectsCollection([$first_project, $second_project]);

        $user = UserTestBuilder::aUser()->build();

        $malformed_planning          = new \Planning(3, 'Malformed lanning', 103, 'Irrelevant', 'Irrelevant');
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->once()
            ->with($user, 103)
            ->andReturn($malformed_planning);

        $this->expectException(NoMilestoneTrackerException::class);
        $this->builder->buildFromContributorProjects($projects, $user);
    }
}
