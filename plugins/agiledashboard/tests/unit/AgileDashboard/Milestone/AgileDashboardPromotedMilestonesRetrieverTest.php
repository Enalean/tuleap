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

namespace Tuleap\AgileDashboard\AgileDashboard\Milestone;

use Tuleap\AgileDashboard\Milestone\AgileDashboardPromotedMilestonesRetriever;
use Tuleap\AgileDashboard\Milestone\PaginatedMilestones;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\Stub\Milestone\Sidebar\CheckMilestonesInSidebarStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementTextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class AgileDashboardPromotedMilestonesRetrieverTest extends TestCase
{
    public function testItReturnsNoMilestone(): void
    {
        $factory = $this->createMock(\Planning_MilestoneFactory::class);
        $factory->method('getPaginatedTopMilestones')->willReturn($this->anEmptyPaginatedMilestone());
        $retriever = new AgileDashboardPromotedMilestonesRetriever(
            $factory,
            ProjectTestBuilder::aProject()->build(),
            CheckMilestonesInSidebarStub::withMilestonesInSidebar()
        );

        self::assertEmpty($retriever->getSidebarPromotedMilestones(UserTestBuilder::buildWithDefaults()));
    }

    public function testItReturnsNoMilestoneWhenConfigShouldNotDisplay(): void
    {
        $factory = $this->createMock(\Planning_MilestoneFactory::class);
        $project = ProjectTestBuilder::aProject()->build();
        $factory->method('getPaginatedTopMilestones')->willReturn($this->aPaginatedMilestoneWith1Milestone($project));
        $retriever = new AgileDashboardPromotedMilestonesRetriever(
            $factory,
            $project,
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar()
        );

        self::assertEmpty($retriever->getSidebarPromotedMilestones(UserTestBuilder::buildWithDefaults()));
    }

    public function testItReturnsMilestonesAsPromotedItem(): void
    {
        $factory     = $this->createMock(\Planning_MilestoneFactory::class);
        $project     = ProjectTestBuilder::aProject()->build();
        $tracker     = TrackerTestBuilder::aTracker()->withId(1)->build();
        $title_field = TrackerFormElementTextFieldBuilder::aTextField(1)->build();
        \Tracker_Semantic_Title::setInstance(
            new \Tracker_Semantic_Title($tracker, $title_field),
            $tracker
        );
        $description_field = TrackerFormElementTextFieldBuilder::aTextField(2)->build();
        \Tracker_Semantic_Description::setInstance(
            new \Tracker_Semantic_Description($tracker, $description_field),
            $tracker
        );
        $changeset   = ChangesetTestBuilder::aChangeset('301')->build();
        $title_value = $this->createMock(\Tracker_Artifact_ChangesetValue_Text::class);
        $title_value->method('getValue')->willReturn('Title');
        $changeset->setFieldValue($title_field, $title_value);
        $description_value = $this->createMock(\Tracker_Artifact_ChangesetValue_Text::class);
        $description_value->method('getValue')->willReturn('Description');
        $changeset->setFieldValue($description_field, $description_value);
        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($tracker)->withChangesets($changeset)->build();
        $factory->method('getPaginatedTopMilestones')->willReturn($this->aPaginatedMilestoneWith1MilestoneDetails($project, $artifact));
        $retriever = new AgileDashboardPromotedMilestonesRetriever(
            $factory,
            $project,
            CheckMilestonesInSidebarStub::withMilestonesInSidebar()
        );

        $items = $retriever->getSidebarPromotedMilestones(UserTestBuilder::buildWithDefaults());
        self::assertCount(1, $items);
        $item = $items[0];
        self::assertSame('/plugins/agiledashboard/?group_id=101&planning_id=105&action=show&aid=1&pane=planning-v2', $item->href);
        self::assertSame('Title', $item->label);
        self::assertSame('Description', $item->description);
        self::assertFalse($item->is_active);
        self::assertEmpty($item->quick_link_add);
    }

    private function anEmptyPaginatedMilestone(): PaginatedMilestones
    {
        return new PaginatedMilestones([], 0);
    }

    private function aPaginatedMilestoneWith1Milestone(\Project $project): PaginatedMilestones
    {
        return new PaginatedMilestones([
            new \Planning_ArtifactMilestone(
                $project,
                $this->createMock(\Planning::class),
                ArtifactTestBuilder::anArtifact(1)->build(),
                $this->createMock(ScrumForMonoMilestoneChecker::class)
            ),
        ], 1);
    }

    private function aPaginatedMilestoneWith1MilestoneDetails(\Project $project, Artifact $artifact): PaginatedMilestones
    {
        $planning = $this->createMock(\Planning::class);
        $planning->method('getId')->willReturn(105);

        return new PaginatedMilestones([
            new \Planning_ArtifactMilestone(
                $project,
                $planning,
                $artifact,
                $this->createMock(ScrumForMonoMilestoneChecker::class)
            ),
        ], 1);
    }
}
