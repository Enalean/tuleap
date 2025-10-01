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

namespace Tuleap\AgileDashboard\REST\v1\Milestone;

use AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder;
use EventManager;
use PHPUnit\Framework\MockObject\MockObject;
use Planning;
use Planning_ArtifactMilestone;
use Planning_MilestoneFactory;
use PlanningFactory;
use Project;
use Tracker_Artifact_Changeset;
use Tuleap\AgileDashboard\Milestone\Backlog\MilestoneBacklog;
use Tuleap\AgileDashboard\Milestone\Backlog\MilestoneBacklogFactory;
use Tuleap\AgileDashboard\Milestone\PaginatedMilestones;
use Tuleap\AgileDashboard\Milestone\ParentTrackerRetriever;
use Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Project\ProjectBackground\ProjectBackgroundConfiguration;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MilestoneRepresentationBuilderTest extends TestCase
{
    private const PROJECT_ID = 101;

    private MilestoneRepresentationBuilder $builder;
    private Planning_MilestoneFactory&MockObject $milestone_factory;
    private MilestoneBacklogFactory&MockObject $backlog_factory;
    private EventManager&MockObject $event_manager;
    private ParentTrackerRetriever&MockObject $parent_tracker_retriever;
    private AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder&MockObject $sub_milestone_finder;
    private PlanningFactory&MockObject $planning_factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->milestone_factory        = $this->createMock(Planning_MilestoneFactory::class);
        $this->backlog_factory          = $this->createMock(MilestoneBacklogFactory::class);
        $this->event_manager            = $this->createMock(EventManager::class);
        $this->parent_tracker_retriever = $this->createMock(ParentTrackerRetriever::class);
        $this->sub_milestone_finder     = $this->createMock(AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder::class);
        $this->planning_factory         = $this->createMock(PlanningFactory::class);
        $this->builder                  = new MilestoneRepresentationBuilder(
            $this->milestone_factory,
            $this->backlog_factory,
            $this->event_manager,
            $this->parent_tracker_retriever,
            $this->sub_milestone_finder,
            $this->planning_factory,
            $this->createMock(ProjectBackgroundConfiguration::class)
        );
    }

    public function testItBuildsRepresentationsFromCollection(): void
    {
        $backlog = $this->createMock(MilestoneBacklog::class);
        $backlog->method('getDescendantTrackers')->willReturn([]);
        $this->backlog_factory->method('getBacklog')->willReturn($backlog);
        $this->event_manager->method('processEvent');
        $this->parent_tracker_retriever->method('getCreatableParentTrackers')->willReturn([]);
        $this->milestone_factory->method('userCanChangePrioritiesInMilestone')->willReturn(true);

        $this->sub_milestone_finder->method('findFirstSubmilestoneTracker')->willReturn(null);
        $this->planning_factory->method('getChildrenPlanning')->willReturn(null);

        $project           = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
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

        self::assertSame(4, $representations->getTotalSize());
        $first_representation = $representations->getMilestonesRepresentations()[0];
        self::assertSame(22, $first_representation->id);
        $second_representation = $representations->getMilestonesRepresentations()[1];
        self::assertSame(23, $second_representation->id);
    }

    private function buildMilestoneTracker(Project $project): Tracker
    {
        return TrackerTestBuilder::aTracker()
            ->withId(8)
            ->withName('Releases')
            ->withProject($project)
            ->build();
    }

    private function buildBacklogTracker(Project $project): Tracker
    {
        return TrackerTestBuilder::aTracker()
            ->withId(9)
            ->withName('User Stories')
            ->withProject($project)
            ->build();
    }

    private function buildPlanning(Tracker $milestone_tracker, Tracker $backlog_tracker): Planning
    {
        return PlanningBuilder::aPlanning(self::PROJECT_ID)
            ->withMilestoneTracker($milestone_tracker)
            ->withBacklogTrackers($backlog_tracker)
            ->build();
    }

    private function buildMilestone(
        int $artifact_id,
        Project $project,
        Planning $planning,
        Tracker $milestone_tracker,
    ): Planning_ArtifactMilestone {
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getId')->willReturn($artifact_id);
        $artifact->method('getTitle')->willReturn('Test Milestone');
        $artifact->method('getStatus')->willReturn('Ongoing');
        $artifact->method('getSemanticStatusValue')->willReturn(Artifact::STATUS_OPEN);
        $artifact->method('getDescription')->willReturn('Test description');
        $artifact->method('getPostProcessedDescription')->willReturn('Test description');
        $artifact->method('getLastUpdateDate')->willReturn(1);
        $first_changeset = new Tracker_Artifact_Changeset(1, $artifact, 101, 1, 'irrelevant@example.com');
        $artifact->method('getFirstChangeset')->willReturn($first_changeset);
        $artifact->method('getTracker')->willReturn($milestone_tracker);

        return new Planning_ArtifactMilestone(
            $project,
            $planning,
            $artifact,
        );
    }
}
