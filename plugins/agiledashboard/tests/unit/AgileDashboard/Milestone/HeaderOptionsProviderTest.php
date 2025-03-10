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

namespace Tuleap\AgileDashboard\Milestone;

use AgileDashboard_Milestone_Backlog_Backlog;
use AgileDashboard_Milestone_Backlog_BacklogFactory;
use AgileDashboard_PaneInfoIdentifier;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Planning_ArtifactMilestone;
use Planning_Milestone;
use Planning_VirtualTopMilestone;
use Tracker;
use Tuleap\AgileDashboard\Planning\HeaderOptionsForPlanningProvider;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Layout\NewDropdown\CurrentContextSectionToHeaderOptionsInserter;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\NewDropdown\TrackerNewDropdownLinkPresenterBuilder;
use Tuleap\Tracker\Permission\TrackerPermissionType;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Permission\RetrieveUserPermissionOnTrackersStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HeaderOptionsProviderTest extends TestCase
{
    private HeaderOptionsProvider $provider;
    private PFUser $user;
    private Planning_Milestone $milestone;
    private AgileDashboard_Milestone_Backlog_Backlog&MockObject $backlog;
    private ParentTrackerRetriever&MockObject $parent_retriever;
    private Tracker $epic;
    private Tracker $story;
    private Tracker $requirement;
    private Tracker $task;
    private Tracker $top_requirement;

    protected function setUp(): void
    {
        $backlog_factory        = $this->createMock(AgileDashboard_Milestone_Backlog_BacklogFactory::class);
        $this->parent_retriever = $this->createMock(ParentTrackerRetriever::class);

        $header_options_for_planning_provider = $this->createMock(HeaderOptionsForPlanningProvider::class);

        $this->provider = new HeaderOptionsProvider(
            $backlog_factory,
            new AgileDashboard_PaneInfoIdentifier(),
            new TrackerNewDropdownLinkPresenterBuilder(),
            $header_options_for_planning_provider,
            $this->parent_retriever,
            new CurrentContextSectionToHeaderOptionsInserter(),
            RetrieveUserPermissionOnTrackersStub::build()->withPermissionOn([101, 102, 104], TrackerPermissionType::PERMISSION_SUBMIT),
        );

        $this->user      = UserTestBuilder::buildWithDefaults();
        $this->milestone = new Planning_ArtifactMilestone(
            ProjectTestBuilder::aProject()->withId(101)->build(),
            PlanningBuilder::aPlanning(101)->withId(69)->build(),
            ArtifactTestBuilder::anArtifact(42)->withTitle('Milestone title')->build(),
        );

        $this->backlog = $this->createMock(AgileDashboard_Milestone_Backlog_Backlog::class);
        $backlog_factory->method('getBacklog')
            ->with($this->user, $this->milestone)
            ->willReturn($this->backlog);

        $this->epic            = TrackerTestBuilder::aTracker()
            ->withId(101)
            ->withName('epic')
            ->build();
        $this->story           = TrackerTestBuilder::aTracker()
            ->withId(102)
            ->withName('story')
            ->build();
        $this->requirement     = TrackerTestBuilder::aTracker()
            ->withId(103)
            ->withName('req')
            ->build();
        $this->task            = TrackerTestBuilder::aTracker()
            ->withId(104)
            ->withName('task')
            ->build();
        $this->top_requirement = TrackerTestBuilder::aTracker()
            ->withId(105)
            ->withName('top')
            ->build();
    }

    public function testCurrentContextSectionForMilestone(): void
    {
        $this->backlog->method('getDescendantTrackers')->willReturn([$this->story, $this->requirement, $this->task]);
        $this->parent_retriever->method('getCreatableParentTrackers')->willReturn([$this->epic, $this->top_requirement]);

        $section = $this->provider->getCurrentContextSection($this->user, $this->milestone, 'details');
        self::assertEquals('Milestone title', $section->unwrapOr(null)->label);
        self::assertCount(3, $section->unwrapOr(null)->links);
        self::assertEquals('New story', $section->unwrapOr(null)->links[0]->label);
        self::assertEquals('New task', $section->unwrapOr(null)->links[1]->label);
        self::assertEquals('New epic', $section->unwrapOr(null)->links[2]->label);
    }

    public function testCurrentContextSectionForTopBacklog(): void
    {
        $planning      = PlanningBuilder::aPlanning(101)
            ->withId(69)
            ->withBacklogTrackers($this->story, $this->requirement, $this->task)
            ->withMilestoneTracker(TrackerTestBuilder::aTracker()->withUserCanSubmit(true)->build())
            ->build();
        $top_milestone = new Planning_VirtualTopMilestone(ProjectTestBuilder::aProject()->withId(101)->build(), $planning);
        $this->parent_retriever->method('getCreatableParentTrackers')->willReturn([$this->epic, $this->top_requirement]);

        $section = $this->provider->getCurrentContextSection($this->user, $top_milestone, 'details');
        self::assertEquals('Top backlog', $section->unwrapOr(null)->label);
        self::assertCount(3, $section->unwrapOr(null)->links);
        self::assertEquals('New story', $section->unwrapOr(null)->links[0]->label);
        self::assertEquals('New task', $section->unwrapOr(null)->links[1]->label);
        self::assertEquals('New epic', $section->unwrapOr(null)->links[2]->label);
    }
}
