<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Sidebar;

use Planning;
use Planning_ArtifactMilestone;
use Planning_MilestoneFactory;
use Planning_NoPlanningsException;
use Planning_VirtualTopMilestone;
use Tracker_Semantic_Description;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\Stub\Milestone\Sidebar\BuildPromotedMilestoneListStub;
use Tuleap\AgileDashboard\Stub\Milestone\Sidebar\CheckMilestonesInSidebarStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementTextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class AgileDashboardPromotedMilestonesRetrieverTest extends TestCase
{
    protected function tearDown(): void
    {
        Tracker_Semantic_Description::clearInstances();
    }

    public function testItReturnsNoMilestoneWhenConfigShouldNotDisplay(): void
    {
        $retriever = new AgileDashboardPromotedMilestonesRetriever(
            $this->createMock(Planning_MilestoneFactory::class),
            ProjectTestBuilder::aProject()->build(),
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
            BuildPromotedMilestoneListStub::buildFromEmpty()
        );

        self::assertEmpty($retriever->getSidebarPromotedMilestones(UserTestBuilder::buildWithDefaults(), 'whatever'));
    }

    public function testItReturnsNoMilestonesWhenFactoryThrowNoPlanning(): void
    {
        $factory = $this->createMock(Planning_MilestoneFactory::class);
        $factory->method('getVirtualTopMilestone')->willThrowException(new Planning_NoPlanningsException());

        $retriever = new AgileDashboardPromotedMilestonesRetriever(
            $factory,
            ProjectTestBuilder::aProject()->build(),
            CheckMilestonesInSidebarStub::withMilestonesInSidebar(),
            BuildPromotedMilestoneListStub::buildFromEmpty()
        );

        self::assertEmpty($retriever->getSidebarPromotedMilestones(UserTestBuilder::buildWithDefaults(), 'whatever'));
    }

    /**
     * @testWith ["whatever", false, false]
     *           ["milestone-5", true, false]
     *           ["milestone-6", true, true]
     */
    public function testItBuildsPromotedMilestones(
        string $active_promoted_item_id,
        bool $should_milestone_be_active,
        bool $should_sub_milestone_be_active,
    ): void {
        $user              = UserTestBuilder::buildWithDefaults();
        $milestone_factory = $this->createMock(Planning_MilestoneFactory::class);
        $project           = ProjectTestBuilder::aProject()->build();
        $planning          = $this->createMock(Planning::class);
        $virtual_milestone = new Planning_VirtualTopMilestone($project, $planning);
        $tracker           = TrackerTestBuilder::aTracker()->withId(3)->build();
        $planning->method('getPlanningTrackerId')->willReturn($tracker->getId());
        $planning->method('getId')->willReturn(1);

        $artifact              = ArtifactTestBuilder::anArtifact(5)
            ->withTitle('Title')
            ->withDescription('Description')
            ->build();
        $sub_artifact          = ArtifactTestBuilder::anArtifact(6)
            ->withTitle('Sub Title')
            ->withDescription('Sub Description')
            ->build();
        $promoted_artifact     = new Planning_ArtifactMilestone(
            $project,
            $planning,
            $artifact,
            $this->createMock(ScrumForMonoMilestoneChecker::class)
        );
        $promoted_sub_artifact = new Planning_ArtifactMilestone(
            $project,
            $planning,
            $sub_artifact,
            $this->createMock(ScrumForMonoMilestoneChecker::class)
        );

        $milestone_factory->method('getVirtualTopMilestone')->willReturn($virtual_milestone);
        $semantic_description = $this->createMock(Tracker_Semantic_Description::class);
        $semantic_description->method("getField")->willReturn(TrackerFormElementTextFieldBuilder::aTextField(1)->build());
        Tracker_Semantic_Description::setInstance($semantic_description, $tracker);

        $retriever = new AgileDashboardPromotedMilestonesRetriever(
            $milestone_factory,
            $project,
            CheckMilestonesInSidebarStub::withMilestonesInSidebar(),
            BuildPromotedMilestoneListStub::buildWithValues($promoted_artifact, $promoted_sub_artifact),
        );

        $items = $retriever->getSidebarPromotedMilestones($user, $active_promoted_item_id);
        self::assertCount(1, $items);
        self::assertEquals("Title", $items[0]->label);
        self::assertSame('Description', $items[0]->description);
        self::assertSame($should_milestone_be_active, $items[0]->is_active);
        self::assertEmpty($items[0]->quick_link_add);
        self::assertCount(1, $items[0]->items);
        self::assertEquals("Sub Title", $items[0]->items[0]->label);
        self::assertSame('Sub Description', $items[0]->items[0]->description);
        self::assertSame($should_sub_milestone_be_active, $items[0]->items[0]->is_active);
        self::assertEmpty($items[0]->items[0]->quick_link_add);
    }
}
