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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\REST\v1\Milestone;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\AgileDashboard\Milestone\PaginatedMilestones;
use Tuleap\AgileDashboard\Milestone\ParentTrackerRetriever;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\TrackerColor;

final class MilestoneRepresentationBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MilestoneRepresentationBuilder
     */
    private $builder;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var \AgileDashboard_Milestone_Backlog_BacklogFactory|M\LegacyMockInterface|M\MockInterface
     */
    private $backlog_factory;
    /**
     * @var \EventManager|M\LegacyMockInterface|M\MockInterface
     */
    private $event_manager;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ScrumForMonoMilestoneChecker
     */
    private $mono_milestone_checker;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ParentTrackerRetriever
     */
    private $parent_tracker_retriever;
    /**
     * @var \AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder|M\LegacyMockInterface|M\MockInterface
     */
    private $sub_milestone_finder;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\PlanningFactory
     */
    private $planning_factory;

    protected function setUp(): void
    {
        $this->milestone_factory        = M::mock(\Planning_MilestoneFactory::class);
        $this->backlog_factory          = M::mock(\AgileDashboard_Milestone_Backlog_BacklogFactory::class);
        $this->event_manager            = M::mock(\EventManager::class);
        $this->mono_milestone_checker   = M::mock(ScrumForMonoMilestoneChecker::class);
        $this->parent_tracker_retriever = M::mock(ParentTrackerRetriever::class);
        $this->sub_milestone_finder     = M::mock(\AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder::class);
        $this->planning_factory         = M::mock(\PlanningFactory::class);
        $this->builder                  = new MilestoneRepresentationBuilder(
            $this->milestone_factory,
            $this->backlog_factory,
            $this->event_manager,
            $this->mono_milestone_checker,
            $this->parent_tracker_retriever,
            $this->sub_milestone_finder,
            $this->planning_factory
        );
    }

    public function testItBuildsRepresentationsFromCollection(): void
    {
        $this->mono_milestone_checker->shouldReceive('isMonoMilestoneEnabled')->andReturnFalse();
        $backlog = M::mock(\AgileDashboard_Milestone_Backlog_Backlog::class);
        $backlog->shouldReceive('getDescendantTrackers')->andReturn([]);
        $this->backlog_factory->shouldReceive('getBacklog')->andReturn($backlog);
        $this->event_manager->shouldReceive('processEvent');
        $this->parent_tracker_retriever->shouldReceive('getCreatableParentTrackers')->andReturn([]);
        $this->milestone_factory->shouldReceive('userCanChangePrioritiesInMilestone')->andReturnTrue();

        $this->sub_milestone_finder->shouldReceive('findFirstSubmilestoneTracker')->andReturnNull();
        $this->planning_factory->shouldReceive('getChildrenPlanning')->andReturnNull();

        $project           = new \Project(['group_id' => 101, 'group_name' => 'Test Project']);
        $milestone_tracker = $this->buildMilestoneTracker($project);
        $backlog_tracker   = $this->buildBacklogTracker($project);
        $planning          = $this->buildPlanning($milestone_tracker, $backlog_tracker);
        $first_milestone   = $this->buildMilestone(22, $project, $planning, $milestone_tracker);
        $second_milestone  = $this->buildMilestone(23, $project, $planning, $milestone_tracker);
        $collection        = new PaginatedMilestones([$first_milestone, $second_milestone], 4);
        $user              = UserTestBuilder::aUser()->build();

        $representations = $this->builder->buildRepresentationsFromCollection(
            $collection,
            $user,
            MilestoneRepresentation::SLIM
        );

        $this->assertSame(4, $representations->getTotalSize());
        $first_representation = $representations->getMilestonesRepresentations()[0];
        $this->assertSame(22, $first_representation->id);
        $second_representation = $representations->getMilestonesRepresentations()[1];
        $this->assertSame(23, $second_representation->id);
    }

    public function testItBuildsRepresentationsWithScrumMonoMilestone(): void
    {
        $this->mono_milestone_checker->shouldReceive('isMonoMilestoneEnabled')->andReturnTrue();
        $backlog = M::mock(\AgileDashboard_Milestone_Backlog_Backlog::class);
        $backlog->shouldReceive('getDescendantTrackers')->andReturn([]);
        $this->backlog_factory->shouldReceive('getBacklog')->andReturn($backlog);
        $this->event_manager->shouldReceive('processEvent');
        $this->parent_tracker_retriever->shouldReceive('getCreatableParentTrackers')->andReturn([]);
        $this->milestone_factory->shouldReceive('userCanChangePrioritiesInMilestone')->andReturnTrue();

        $project           = new \Project(['group_id' => 101, 'group_name' => 'Test Project']);
        $milestone_tracker = $this->buildMilestoneTracker($project);
        $backlog_tracker   = $this->buildBacklogTracker($project);
        $planning          = $this->buildPlanning($milestone_tracker, $backlog_tracker);

        $this->sub_milestone_finder->shouldReceive('findFirstSubmilestoneTracker')->andReturn($milestone_tracker);
        $this->planning_factory->shouldReceive('getPlanning')->andReturn($planning);

        $first_milestone  = $this->buildMilestone(24, $project, $planning, $milestone_tracker);
        $second_milestone = $this->buildMilestone(25, $project, $planning, $milestone_tracker);
        $collection       = new PaginatedMilestones([$first_milestone, $second_milestone], 4);
        $user             = UserTestBuilder::aUser()->build();

        $representations = $this->builder->buildRepresentationsFromCollection(
            $collection,
            $user,
            MilestoneRepresentation::SLIM
        );

        $this->assertSame(4, $representations->getTotalSize());
        $first_representation = $representations->getMilestonesRepresentations()[0];
        $this->assertSame(24, $first_representation->id);
        $this->assertSame('Releases', $first_representation->sub_milestone_type->label);
        $second_representation = $representations->getMilestonesRepresentations()[1];
        $this->assertSame(25, $second_representation->id);
        $this->assertSame('Releases', $first_representation->sub_milestone_type->label);
    }

    private function buildMilestoneTracker(\Project $project): \Tracker
    {
        $milestone_tracker = $this->buildTestTracker(8, 'Releases');
        $milestone_tracker->setProject($project);
        return $milestone_tracker;
    }

    private function buildBacklogTracker(\Project $project): \Tracker
    {
        $backlog_tracker = $this->buildTestTracker(9, 'User Stories');
        $backlog_tracker->setProject($project);
        return $backlog_tracker;
    }

    private function buildPlanning(\Tracker $milestone_tracker, \Tracker $backlog_tracker): \Planning
    {
        $planning = new \Planning(1, 'Release Planning', 101, 'Irrelevant', 'Irrelevant');
        $planning->setPlanningTracker($milestone_tracker);
        $planning->setBacklogTrackers([$backlog_tracker]);
        return $planning;
    }

    private function buildMilestone(
        int $artifact_id,
        \Project $project,
        \Planning $planning,
        \Tracker $milestone_tracker
    ): \Planning_ArtifactMilestone {
        $artifact = M::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->shouldReceive('getId')->andReturn($artifact_id);
        $artifact->shouldReceive('getTitle')->andReturn('Test Milestone');
        $artifact->shouldReceive('getStatus')->andReturn('Ongoing');
        $artifact->shouldReceive('getSemanticStatusValue')->andReturn(\Tuleap\Tracker\Artifact\Artifact::STATUS_OPEN);
        $artifact->shouldReceive('getDescription')->andReturn('Test description');
        $artifact->shouldReceive('getPostProcessedDescription')->andReturn('Test description');
        $artifact->shouldReceive('getLastUpdateDate')->andReturn(1);
        $first_changeset = new \Tracker_Artifact_Changeset(1, $artifact, 101, 1, 'irrelevant@example.com');
        $artifact->shouldReceive('getFirstChangeset')->andReturn($first_changeset);
        $artifact->shouldReceive('getTracker')->andReturn($milestone_tracker);

        return new \Planning_ArtifactMilestone(
            $project,
            $planning,
            $artifact,
            $this->mono_milestone_checker
        );
    }

    private function buildTestTracker(int $tracker_id, string $name): \Tracker
    {
        return new \Tracker(
            $tracker_id,
            null,
            $name,
            'Irrelevant',
            'irrelevant',
            false,
            null,
            null,
            null,
            null,
            true,
            false,
            \Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            TrackerColor::default(),
            false
        );
    }
}
