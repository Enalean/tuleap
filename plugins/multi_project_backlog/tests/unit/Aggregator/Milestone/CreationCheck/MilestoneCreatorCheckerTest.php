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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\CreationCheck;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Planning_VirtualTopMilestone;
use Project;
use Psr\Log\NullLogger;
use Tuleap\MultiProjectBacklog\Aggregator\ContributorProjectsCollection;
use Tuleap\MultiProjectBacklog\Aggregator\ContributorProjectsCollectionBuilder;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerCollection;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerCollectionBuilder;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerRetrievalException;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFieldCollection;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFieldCollectionBuilder;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFieldRetrievalException;
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
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SynchronizedFieldCollectionBuilder
     */
    private $field_collection_builder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SemanticChecker
     */
    private $semantic_checker;

    /**
     * @var Project
     */
    private $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projects_builder         = Mockery::mock(ContributorProjectsCollectionBuilder::class);
        $this->trackers_builder         = Mockery::mock(MilestoneTrackerCollectionBuilder::class);
        $this->field_collection_builder = Mockery::mock(SynchronizedFieldCollectionBuilder::class);
        $this->semantic_checker         = Mockery::mock(SemanticChecker::class);

        $this->checker = new MilestoneCreatorChecker(
            $this->projects_builder,
            $this->trackers_builder,
            $this->field_collection_builder,
            $this->semantic_checker,
            new NullLogger()
        );

        $this->project = new Project([
            'group_id'   => '101',
            'unix_group_name' => 'proj01'
        ]);
    }

    public function testItReturnsTrueIfAllChecksAreOk(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $aggregator_milestone = Mockery::mock(Planning_VirtualTopMilestone::class);
        $aggregator_milestone->shouldReceive('getProject')->andReturn($this->project);

        $this->mockContributorMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->once()
            ->andReturnTrue();

        $field = Mockery::mock(\Tracker_FormElement_Field::class);
        $field->shouldReceive('userCanSubmit')->andReturnTrue();
        $field->shouldReceive('userCanUpdate')->andReturnTrue();
        $this->field_collection_builder->shouldReceive('buildFromMilestoneTrackers')
            ->once()
            ->andReturn(new SynchronizedFieldCollection([$field]));

        $this->assertTrue($this->checker->canMilestoneBeCreated($aggregator_milestone, $user));
    }

    public function testItReturnsTrueWhenAProjectHasNoContributorProjects(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $aggregator_milestone = Mockery::mock(Planning_VirtualTopMilestone::class);
        $aggregator_milestone->shouldReceive('getProject')->andReturn($this->project);

        $this->projects_builder->shouldReceive('getContributorProjectForAGivenAggregatorProject')
            ->andReturn(new ContributorProjectsCollection([]));

        $this->assertTrue($this->checker->canMilestoneBeCreated($aggregator_milestone, $user));
    }

    public function testItReturnsFalseIfOneProjectDoesNotHaveARootPlanningWithAMilestoneTracker(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $aggregator_milestone = Mockery::mock(Planning_VirtualTopMilestone::class);
        $aggregator_milestone->shouldReceive('getProject')->andReturn($this->project);

        $first_contributor_project  = new \Project(['group_id' => '104']);
        $second_contributor_project = new \Project(['group_id' => '198']);
        $this->projects_builder->shouldReceive('getContributorProjectForAGivenAggregatorProject')
            ->once()
            ->with($this->project)
            ->andReturn(new ContributorProjectsCollection([$first_contributor_project, $second_contributor_project]));
        $this->trackers_builder->shouldReceive('buildFromAggregatorProjectAndItsContributors')
            ->once()
            ->andThrow(
                new class extends \RuntimeException implements MilestoneTrackerRetrievalException {
                }
            );

        $this->assertFalse($this->checker->canMilestoneBeCreated($aggregator_milestone, $user));
    }

    public function testItReturnsFalseIfSemanticsAreNotWellConfigured(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $aggregator_milestone = Mockery::mock(Planning_VirtualTopMilestone::class);
        $aggregator_milestone->shouldReceive('getProject')->andReturn($this->project);

        $this->mockContributorMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->andReturnFalse();

        $this->assertFalse($this->checker->canMilestoneBeCreated($aggregator_milestone, $user));
    }

    public function testItReturnsFalseIfUserCannotSubmitArtifact(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $aggregator_milestone = Mockery::mock(Planning_VirtualTopMilestone::class);
        $aggregator_milestone->shouldReceive('getProject')->andReturn($this->project);

        $this->mockContributorMilestoneTrackers($this->project, false);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->andReturnTrue();

        $this->assertFalse($this->checker->canMilestoneBeCreated($aggregator_milestone, $user));
    }

    public function testItReturnsFalseIfFieldsCantBeExtractedFromMilestoneTrackers(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $aggregator_milestone = Mockery::mock(Planning_VirtualTopMilestone::class);
        $aggregator_milestone->shouldReceive('getProject')->andReturn($this->project);

        $this->mockContributorMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->andReturnTrue();
        $this->field_collection_builder->shouldReceive('buildFromMilestoneTrackers')
            ->andThrow(
                new class extends \RuntimeException implements SynchronizedFieldRetrievalException {
                }
            );

        $this->assertFalse($this->checker->canMilestoneBeCreated($aggregator_milestone, $user));
    }

    public function testItReturnsFalseIfUserCantSubmitOneArtifactLink(): void
    {
        $user                 = UserTestBuilder::aUser()->build();
        $aggregator_milestone = Mockery::mock(Planning_VirtualTopMilestone::class);
        $aggregator_milestone->shouldReceive('getProject')->andReturn($this->project);

        $this->mockContributorMilestoneTrackers($this->project);
        $this->semantic_checker->shouldReceive('areTrackerSemanticsWellConfigured')
            ->andReturnTrue();
        $field = Mockery::mock(\Tracker_FormElement_Field::class);
        $field->shouldReceive('userCanSubmit')->andReturnFalse();
        $this->field_collection_builder->shouldReceive('buildFromMilestoneTrackers')
            ->once()
            ->andReturn(new SynchronizedFieldCollection([$field]));

        $this->assertFalse($this->checker->canMilestoneBeCreated($aggregator_milestone, $user));
    }

    private function mockContributorMilestoneTrackers(Project $project, bool $user_can_submit_artifact = true): void
    {
        $first_contributor_project  = new \Project(['group_id' => '104']);
        $second_contributor_project = new \Project(['group_id' => '198']);
        $this->projects_builder->shouldReceive('getContributorProjectForAGivenAggregatorProject')
            ->once()
            ->with($project)
            ->andReturn(new ContributorProjectsCollection([$first_contributor_project, $second_contributor_project]));
        $first_milestone_tracker = Mockery::mock(\Tracker::class);
        $first_milestone_tracker->shouldReceive('userCanSubmitArtifact')->andReturn($user_can_submit_artifact);
        $second_milestone_tracker = Mockery::mock(\Tracker::class);
        $second_milestone_tracker->shouldReceive('userCanSubmitArtifact')->andReturn($user_can_submit_artifact);
        $this->trackers_builder->shouldReceive('buildFromAggregatorProjectAndItsContributors')
            ->once()
            ->andReturn(new MilestoneTrackerCollection([$first_milestone_tracker, $second_milestone_tracker]));
    }
}
