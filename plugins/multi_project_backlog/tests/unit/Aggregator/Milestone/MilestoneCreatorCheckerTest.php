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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning;
use Planning_VirtualTopMilestone;
use PlanningFactory;
use Project;
use Tuleap\MultiProjectBacklog\Aggregator\ContributorProjectsCollection;
use Tuleap\MultiProjectBacklog\Aggregator\ContributorProjectsCollectionBuilder;

class MilestoneCreatorCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MilestoneCreatorChecker
     */
    private $checker;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ContributorProjectsCollectionBuilder
     */
    private $collection_builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning_VirtualTopMilestone
     */
    private $milestone;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collection_builder = Mockery::mock(ContributorProjectsCollectionBuilder::class);
        $this->planning_factory   = Mockery::mock(PlanningFactory::class);

        $this->checker = new MilestoneCreatorChecker(
            $this->collection_builder,
            $this->planning_factory
        );

        $this->milestone = Mockery::mock(Planning_VirtualTopMilestone::class);
        $this->user      = Mockery::mock(PFUser::class);
    }

    public function testItReturnsTrueIfAllChecksAreOk(): void
    {
        $project = Project::buildForTest();
        $this->milestone->shouldReceive('getProject')->andReturn($project);

        $contributor_project_01 = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn('102')->getMock();
        $contributor_project_02 = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn('103')->getMock();

        $this->collection_builder->shouldReceive('getContributorProjectForAGivenAggregatorProject')
            ->once()
            ->with($project)
            ->andReturn(
                new ContributorProjectsCollection([
                    $contributor_project_01,
                    $contributor_project_02
                ])
            );

        $this->planning_factory->shouldReceive('getRootPlanning')
            ->with($this->user, 102)
            ->once()
            ->andReturn(Mockery::mock(Planning::class));

        $this->planning_factory->shouldReceive('getRootPlanning')
            ->with($this->user, 103)
            ->once()
            ->andReturn(Mockery::mock(Planning::class));

        $this->assertTrue(
            $this->checker->canMilestoneBeCreated(
                $this->milestone,
                $this->user
            )
        );
    }

    public function testItReturnsFalseIfAProjectDoesNotHaveARootPlanning(): void
    {
        $project = Project::buildForTest();
        $this->milestone->shouldReceive('getProject')->andReturn($project);

        $contributor_project_01 = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn('102')->getMock();
        $contributor_project_02 = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn('103')->getMock();

        $this->collection_builder->shouldReceive('getContributorProjectForAGivenAggregatorProject')
            ->once()
            ->with($project)
            ->andReturn(
                new ContributorProjectsCollection([
                    $contributor_project_01,
                    $contributor_project_02
                ])
            );

        $this->planning_factory->shouldReceive('getRootPlanning')
            ->with($this->user, 102)
            ->once()
            ->andReturn(Mockery::mock(Planning::class));

        $this->planning_factory->shouldReceive('getRootPlanning')
            ->with($this->user, 103)
            ->once()
            ->andReturnFalse();

        $this->assertFalse(
            $this->checker->canMilestoneBeCreated(
                $this->milestone,
                $this->user
            )
        );
    }
}
