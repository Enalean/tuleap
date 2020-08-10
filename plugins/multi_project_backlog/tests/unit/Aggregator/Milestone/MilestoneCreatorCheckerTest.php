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
use PHPUnit\Framework\TestCase;
use Planning_VirtualTopMilestone;
use Project;
use Tuleap\MultiProjectBacklog\Aggregator\ContributorProjectsCollection;
use Tuleap\MultiProjectBacklog\Aggregator\ContributorProjectsCollectionBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class MilestoneCreatorCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MilestoneCreatorChecker
     */
    private $checker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ContributorProjectsCollectionBuilder
     */
    private $projects_builder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|MilestoneTrackerCollectionBuilder
     */
    private $trackers_builder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_Semantic_TitleDao
     */
    private $title_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_Semantic_DescriptionDao
     */
    private $description_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_Semantic_StatusDao
     */
    private $status_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projects_builder = Mockery::mock(ContributorProjectsCollectionBuilder::class);
        $this->trackers_builder = Mockery::mock(MilestoneTrackerCollectionBuilder::class);
        $this->title_dao        = Mockery::mock(\Tracker_Semantic_TitleDao::class);
        $this->description_dao  = Mockery::mock(\Tracker_Semantic_DescriptionDao::class);
        $this->status_dao       = Mockery::mock(\Tracker_Semantic_StatusDao::class);

        $this->checker = new MilestoneCreatorChecker(
            $this->projects_builder,
            $this->trackers_builder,
            $this->title_dao,
            $this->description_dao,
            $this->status_dao
        );
    }

    public function testItReturnsTrueIfAllChecksAreOk(): void
    {
        $project              = Project::buildForTest();
        $user                 = UserTestBuilder::aUser()->build();
        $aggregator_milestone = Mockery::mock(Planning_VirtualTopMilestone::class);
        $aggregator_milestone->shouldReceive('getProject')->andReturn($project);

        $this->mockContributorMilestoneTrackers($project, 1024, 2048);
        $this->title_dao->shouldReceive('getNbOfTrackerWithoutSemanticTitleDefined')
            ->once()
            ->with([1024, 2048])
            ->andReturn(0);
        $this->description_dao->shouldReceive('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->once()
            ->with([1024, 2048])
            ->andReturn(0);
        $this->status_dao->shouldReceive('getNbOfTrackerWithoutSemanticStatusDefined')
            ->once()
            ->with([1024, 2048])
            ->andReturn(0);

        $this->assertTrue($this->checker->canMilestoneBeCreated($aggregator_milestone, $user));
    }

    public function testItReturnsTrueWhenAProjectHasNoContributorProjects(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $aggregator_milestone = Mockery::mock(Planning_VirtualTopMilestone::class);
        $aggregator_milestone->shouldReceive('getProject')->andReturn(Project::buildForTest());

        $this->projects_builder->shouldReceive('getContributorProjectForAGivenAggregatorProject')
            ->andReturn(new ContributorProjectsCollection([]));

        $this->assertTrue($this->checker->canMilestoneBeCreated($aggregator_milestone, $user));
    }

    public function testItReturnsFalseIfOneProjectDoesNotHaveARootPlanningWithAMilestoneTracker(): void
    {
        $project              = Project::buildForTest();
        $user                 = UserTestBuilder::aUser()->build();
        $aggregator_milestone = Mockery::mock(Planning_VirtualTopMilestone::class);
        $aggregator_milestone->shouldReceive('getProject')->andReturn($project);

        $first_contributor_project  = new \Project(['group_id' => '104']);
        $second_contributor_project = new \Project(['group_id' => '198']);
        $this->projects_builder->shouldReceive('getContributorProjectForAGivenAggregatorProject')
            ->once()
            ->with($project)
            ->andReturn(new ContributorProjectsCollection([$first_contributor_project, $second_contributor_project]));
        $this->trackers_builder->shouldReceive('buildFromContributorProjects')
            ->once()
            ->andThrow(
                new class extends \RuntimeException implements MilestoneTrackerRetrievalException {
                }
            );

        $this->assertFalse($this->checker->canMilestoneBeCreated($aggregator_milestone, $user));
    }

    public function testItReturnsFalseIfOneMilestoneTrackerDoesNotHaveTitleSemantic(): void
    {
        $project              = Project::buildForTest();
        $user                 = UserTestBuilder::aUser()->build();
        $aggregator_milestone = Mockery::mock(Planning_VirtualTopMilestone::class);
        $aggregator_milestone->shouldReceive('getProject')->andReturn($project);

        $this->mockContributorMilestoneTrackers($project, 1024, 2048);
        $this->title_dao->shouldReceive('getNbOfTrackerWithoutSemanticTitleDefined')
            ->once()
            ->with([1024, 2048])
            ->andReturn(1);

        $this->assertFalse($this->checker->canMilestoneBeCreated($aggregator_milestone, $user));
    }

    public function testItReturnsFalseIfOneMilestoneTrackerDoesNotHaveDescriptionSemantic(): void
    {
        $project              = Project::buildForTest();
        $user                 = UserTestBuilder::aUser()->build();
        $aggregator_milestone = Mockery::mock(Planning_VirtualTopMilestone::class);
        $aggregator_milestone->shouldReceive('getProject')->andReturn($project);

        $this->mockContributorMilestoneTrackers($project, 1024, 2048);
        $this->title_dao->shouldReceive('getNbOfTrackerWithoutSemanticTitleDefined')
            ->once()
            ->with([1024, 2048])
            ->andReturn(0);
        $this->description_dao->shouldReceive('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->once()
            ->with([1024, 2048])
            ->andReturn(1);

        $this->assertFalse($this->checker->canMilestoneBeCreated($aggregator_milestone, $user));
    }

    public function testItReturnsFalseIfOneMilestoneTrackerDoesNotHaveStatusSemantic(): void
    {
        $project              = Project::buildForTest();
        $user                 = UserTestBuilder::aUser()->build();
        $aggregator_milestone = Mockery::mock(Planning_VirtualTopMilestone::class);
        $aggregator_milestone->shouldReceive('getProject')->andReturn($project);

        $this->mockContributorMilestoneTrackers($project, 1024, 2048);
        $this->title_dao->shouldReceive('getNbOfTrackerWithoutSemanticTitleDefined')
            ->once()
            ->with([1024, 2048])
            ->andReturn(0);
        $this->description_dao->shouldReceive('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->once()
            ->with([1024, 2048])
            ->andReturn(0);
        $this->status_dao->shouldReceive('getNbOfTrackerWithoutSemanticStatusDefined')
            ->once()
            ->with([1024, 2048])
            ->andReturn(1);

        $this->assertFalse($this->checker->canMilestoneBeCreated($aggregator_milestone, $user));
    }

    private function mockContributorMilestoneTrackers(
        Project $project,
        int $first_milestone_tracker_id,
        int $second_milestone_tracker_id
    ): void {
        $first_contributor_project  = new \Project(['group_id' => '104']);
        $second_contributor_project = new \Project(['group_id' => '198']);
        $this->projects_builder->shouldReceive('getContributorProjectForAGivenAggregatorProject')
            ->once()
            ->with($project)
            ->andReturn(new ContributorProjectsCollection([$first_contributor_project, $second_contributor_project]));
        $first_milestone_tracker = Mockery::mock(\Tracker::class);
        $first_milestone_tracker->shouldReceive('getId')->andReturn($first_milestone_tracker_id);
        $second_milestone_tracker = Mockery::mock(\Tracker::class);
        $second_milestone_tracker->shouldReceive('getId')->andReturn($second_milestone_tracker_id);
        $this->trackers_builder->shouldReceive('buildFromContributorProjects')
            ->once()
            ->andReturn(new MilestoneTrackerCollection([$first_milestone_tracker, $second_milestone_tracker]));
    }
}
