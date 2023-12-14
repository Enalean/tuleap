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

use DateTime;
use Planning;
use Planning_MilestoneFactory;
use Planning_NoPlanningsException;
use Planning_VirtualTopMilestone;
use PlanningFactory;
use Psr\Log\NullLogger;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_ArtifactFactory;
use Tracker_Semantic_Description;
use Tracker_Semantic_Title;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\Stub\Milestone\RetrieveMilestonesWithSubMilestonesStub;
use Tuleap\AgileDashboard\Stub\Milestone\Sidebar\CheckMilestonesInSidebarStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithEndDate;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueDateTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementDateFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementTextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class AgileDashboardPromotedMilestonesRetrieverTest extends TestCase
{
    public function testItReturnsNoMilestoneWhenConfigShouldNotDisplay(): void
    {
        $retriever = new AgileDashboardPromotedMilestonesRetriever(
            $this->createMock(Planning_MilestoneFactory::class),
            RetrieveMilestonesWithSubMilestonesStub::withoutMilestones(),
            ProjectTestBuilder::aProject()->build(),
            CheckMilestonesInSidebarStub::withoutMilestonesInSidebar(),
            $this->createMock(Tracker_ArtifactFactory::class),
            $this->createMock(PlanningFactory::class),
            $this->createMock(ScrumForMonoMilestoneChecker::class),
            $this->createMock(SemanticTimeframeBuilder::class),
            new NullLogger()
        );

        self::assertEmpty($retriever->getSidebarPromotedMilestones(UserTestBuilder::buildWithDefaults()));
    }

    public function testItReturnsNoMilestonesWhenFactoryThrowNoPlanning(): void
    {
        $factory = $this->createMock(Planning_MilestoneFactory::class);
        $factory->method('getVirtualTopMilestone')->willThrowException(new Planning_NoPlanningsException());

        $retriever = new AgileDashboardPromotedMilestonesRetriever(
            $factory,
            RetrieveMilestonesWithSubMilestonesStub::withoutMilestones(),
            ProjectTestBuilder::aProject()->build(),
            CheckMilestonesInSidebarStub::withMilestonesInSidebar(),
            $this->createMock(Tracker_ArtifactFactory::class),
            $this->createMock(PlanningFactory::class),
            $this->createMock(ScrumForMonoMilestoneChecker::class),
            $this->createMock(SemanticTimeframeBuilder::class),
            new NullLogger()
        );

        self::assertEmpty($retriever->getSidebarPromotedMilestones(UserTestBuilder::buildWithDefaults()));
    }

    public function testItReturnsNoMilestone(): void
    {
        $planning_factory  = $this->createMock(Planning_MilestoneFactory::class);
        $project           = ProjectTestBuilder::aProject()->build();
        $planning          = $this->createMock(Planning::class);
        $virtual_milestone = new Planning_VirtualTopMilestone($project, $planning);
        $planning_factory->method('getVirtualTopMilestone')->willReturn($virtual_milestone);
        $planning->method('getPlanningTrackerId')->willReturn(2);

        $retriever = new AgileDashboardPromotedMilestonesRetriever(
            $planning_factory,
            RetrieveMilestonesWithSubMilestonesStub::withoutMilestones(),
            $project,
            CheckMilestonesInSidebarStub::withMilestonesInSidebar(),
            $this->createMock(Tracker_ArtifactFactory::class),
            $this->createMock(PlanningFactory::class),
            $this->createMock(ScrumForMonoMilestoneChecker::class),
            $this->createMock(SemanticTimeframeBuilder::class),
            new NullLogger()
        );

        self::assertEmpty($retriever->getSidebarPromotedMilestones(UserTestBuilder::buildWithDefaults()));
    }

    public function testItReturnsNoMilestoneWhenNotCurrent(): void
    {
        $user              = UserTestBuilder::buildWithDefaults();
        $milestone_factory = $this->createMock(Planning_MilestoneFactory::class);
        $project           = ProjectTestBuilder::aProject()->build();
        $planning          = $this->createMock(Planning::class);
        $virtual_milestone = new Planning_VirtualTopMilestone($project, $planning);
        $tracker           = TrackerTestBuilder::aTracker()->withId(3)->build();
        $artifact_factory  = $this->createMock(Tracker_ArtifactFactory::class);
        $title_field       = TrackerFormElementTextFieldBuilder::aTextField(301)->build();
        $start_field       = TrackerFormElementDateFieldBuilder::aDateField(302)->build();
        $end_field         = TrackerFormElementDateFieldBuilder::aDateField(303)->build();
        $changeset         = ChangesetTestBuilder::aChangeset('501')->build();
        $title_value       = $this->createMock(Tracker_Artifact_ChangesetValue_Text::class);
        $start_field->setUserCanRead($user, true);
        $end_field->setUserCanRead($user, true);
        $changeset->setFieldValue($title_field, $title_value);
        $changeset->setFieldValue($start_field, ChangesetValueDateTestBuilder::aValue(
            1,
            $changeset,
            $start_field
        )->withTimestamp((new DateTime('-1month'))->getTimestamp())->build());
        $changeset->setFieldValue($end_field, ChangesetValueDateTestBuilder::aValue(
            2,
            $changeset,
            $end_field
        )->withTimestamp((new DateTime('-1day'))->getTimestamp())->build());
        $artifact = ArtifactTestBuilder::anArtifact(5)
            ->userCanView(true)
            ->inTracker($tracker)
            ->withChangesets($changeset)
            ->build();
        Tracker_Semantic_Title::setInstance(new Tracker_Semantic_Title($tracker, $title_field), $tracker);
        $timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $planning_factory  = $this->createMock(PlanningFactory::class);
        Tracker_Semantic_Description::setInstance(new Tracker_Semantic_Description($tracker, null), $tracker);


        $milestone_factory->method('getVirtualTopMilestone')->willReturn($virtual_milestone);
        $planning->method('getPlanningTrackerId')->willReturn($tracker->getId());
        $artifact_factory->method('getInstanceFromRow')->willReturn($artifact);
        $timeframe_builder->method('getSemantic')->with($tracker)->willReturn(new SemanticTimeframe($tracker, new TimeframeWithEndDate(
            $start_field,
            $end_field,
        )));
        $planning_factory->method('getPlanningByPlanningTracker')->with($tracker)->willReturn($planning);
        $planning->method('getId')->willReturn(105);
        $title_value->method('getValue')->willReturn('Title');

        $retriever = new AgileDashboardPromotedMilestonesRetriever(
            $milestone_factory,
            RetrieveMilestonesWithSubMilestonesStub::withMilestones([$this->aMilestoneArray($tracker->getId(), $artifact->getId())]),
            $project,
            CheckMilestonesInSidebarStub::withMilestonesInSidebar(),
            $artifact_factory,
            $planning_factory,
            $this->createMock(ScrumForMonoMilestoneChecker::class),
            $timeframe_builder,
            new NullLogger()
        );

        $items = $retriever->getSidebarPromotedMilestones($user);
        self::assertEmpty($items);
    }

    public function testItReturnsMilestonesAsPromotedItem(): void
    {
        $user              = UserTestBuilder::buildWithDefaults();
        $milestone_factory = $this->createMock(Planning_MilestoneFactory::class);
        $project           = ProjectTestBuilder::aProject()->build();
        $planning          = $this->createMock(Planning::class);
        $virtual_milestone = new Planning_VirtualTopMilestone($project, $planning);
        $tracker           = TrackerTestBuilder::aTracker()->withId(3)->build();
        $artifact_factory  = $this->createMock(Tracker_ArtifactFactory::class);
        $title_field       = TrackerFormElementTextFieldBuilder::aTextField(301)->build();
        $start_field       = TrackerFormElementDateFieldBuilder::aDateField(302)->build();
        $end_field         = TrackerFormElementDateFieldBuilder::aDateField(303)->build();
        $changeset         = ChangesetTestBuilder::aChangeset('501')->build();
        $title_value       = $this->createMock(Tracker_Artifact_ChangesetValue_Text::class);
        $start_field->setUserCanRead($user, true);
        $end_field->setUserCanRead($user, true);
        $changeset->setFieldValue($title_field, $title_value);
        $changeset->setFieldValue($start_field, ChangesetValueDateTestBuilder::aValue(
            1,
            $changeset,
            $start_field
        )->withTimestamp((new DateTime('-1day'))->getTimestamp())->build());
        $changeset->setFieldValue($end_field, ChangesetValueDateTestBuilder::aValue(
            2,
            $changeset,
            $end_field
        )->withTimestamp((new DateTime('+1day'))->getTimestamp())->build());
        $artifact = ArtifactTestBuilder::anArtifact(5)
            ->userCanView(true)
            ->inTracker($tracker)
            ->withChangesets($changeset)
            ->withTitle('Title')
            ->build();
        Tracker_Semantic_Title::setInstance(new Tracker_Semantic_Title($tracker, $title_field), $tracker);
        $timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $planning_factory  = $this->createMock(PlanningFactory::class);
        Tracker_Semantic_Description::setInstance(new Tracker_Semantic_Description($tracker, null), $tracker);


        $milestone_factory->method('getVirtualTopMilestone')->willReturn($virtual_milestone);
        $planning->method('getPlanningTrackerId')->willReturn($tracker->getId());
        $artifact_factory->method('getInstanceFromRow')->willReturn($artifact);
        $timeframe_builder->method('getSemantic')->with($tracker)->willReturn(new SemanticTimeframe($tracker, new TimeframeWithEndDate(
            $start_field,
            $end_field,
        )));
        $planning_factory->method('getPlanningByPlanningTracker')->with($tracker)->willReturn($planning);
        $planning->method('getId')->willReturn(105);

        $retriever = new AgileDashboardPromotedMilestonesRetriever(
            $milestone_factory,
            RetrieveMilestonesWithSubMilestonesStub::withMilestones([$this->aMilestoneArray($tracker->getId(), $artifact->getId())]),
            $project,
            CheckMilestonesInSidebarStub::withMilestonesInSidebar(),
            $artifact_factory,
            $planning_factory,
            $this->createMock(ScrumForMonoMilestoneChecker::class),
            $timeframe_builder,
            new NullLogger()
        );

        $items = $retriever->getSidebarPromotedMilestones($user);
        self::assertCount(1, $items);
        $item = $items[0];
        self::assertSame('/plugins/agiledashboard/?group_id=101&planning_id=105&action=show&aid=5&pane=planning-v2', $item->href);
        self::assertSame('Title', $item->label);
        self::assertSame('', $item->description);
        self::assertFalse($item->is_active);
        self::assertEmpty($item->quick_link_add);
        self::assertEmpty($item->items);
    }

    public function testItReturnsMaximum5Items(): void
    {
        $user              = UserTestBuilder::buildWithDefaults();
        $milestone_factory = $this->createMock(Planning_MilestoneFactory::class);
        $project           = ProjectTestBuilder::aProject()->build();
        $planning          = $this->createMock(Planning::class);
        $virtual_milestone = new Planning_VirtualTopMilestone($project, $planning);
        $tracker           = TrackerTestBuilder::aTracker()->withId(3)->build();
        $artifact_factory  = $this->createMock(Tracker_ArtifactFactory::class);
        $title_field       = TrackerFormElementTextFieldBuilder::aTextField(301)->build();
        $start_field       = TrackerFormElementDateFieldBuilder::aDateField(302)->build();
        $end_field         = TrackerFormElementDateFieldBuilder::aDateField(303)->build();
        $changeset         = ChangesetTestBuilder::aChangeset('501')->build();
        $title_value       = $this->createMock(Tracker_Artifact_ChangesetValue_Text::class);
        $start_field->setUserCanRead($user, true);
        $end_field->setUserCanRead($user, true);
        $changeset->setFieldValue($title_field, $title_value);
        $changeset->setFieldValue($start_field, ChangesetValueDateTestBuilder::aValue(
            1,
            $changeset,
            $start_field
        )->withTimestamp((new DateTime('-1day'))->getTimestamp())->build());
        $changeset->setFieldValue($end_field, ChangesetValueDateTestBuilder::aValue(
            2,
            $changeset,
            $end_field
        )->withTimestamp((new DateTime('+1day'))->getTimestamp())->build());
        $artifact  = ArtifactTestBuilder::anArtifact(5)
            ->userCanView(true)
            ->inTracker($tracker)
            ->withChangesets($changeset)
            ->withTitle('Title')
            ->build();
        $artifacts = [$artifact];
        for ($i = 1; $i < 5; $i++) {
            $artifacts[] = ArtifactTestBuilder::anArtifact(5 + $i)
                ->userCanView(true)
                ->inTracker($tracker)
                ->withChangesets($changeset)
                ->withTitle('Title')
                ->build();
        }
        Tracker_Semantic_Title::setInstance(new Tracker_Semantic_Title($tracker, $title_field), $tracker);
        $timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $planning_factory  = $this->createMock(PlanningFactory::class);
        Tracker_Semantic_Description::setInstance(new Tracker_Semantic_Description($tracker, null), $tracker);


        $milestone_factory->method('getVirtualTopMilestone')->willReturn($virtual_milestone);
        $planning->method('getPlanningTrackerId')->willReturn($tracker->getId());
        $artifact_factory->expects(self::exactly(5))->method('getInstanceFromRow')
            ->willReturnOnConsecutiveCalls(...$artifacts);
        $timeframe_builder->method('getSemantic')->with($tracker)->willReturn(new SemanticTimeframe($tracker, new TimeframeWithEndDate(
            $start_field,
            $end_field,
        )));
        $planning_factory->method('getPlanningByPlanningTracker')->with($tracker)->willReturn($planning);
        $planning->method('getId')->willReturn(105);
        $title_value->method('getValue')->willReturn('Title');

        $retriever = new AgileDashboardPromotedMilestonesRetriever(
            $milestone_factory,
            RetrieveMilestonesWithSubMilestonesStub::withMilestones($this->aMilestonesArray10($tracker->getId(), $artifact->getId())),
            $project,
            CheckMilestonesInSidebarStub::withMilestonesInSidebar(),
            $artifact_factory,
            $planning_factory,
            $this->createMock(ScrumForMonoMilestoneChecker::class),
            $timeframe_builder,
            new NullLogger()
        );

        $items = $retriever->getSidebarPromotedMilestones($user);
        self::assertCount(5, $items);
    }

    public function testItReturnsAMilestoneWithASubMilestone(): void
    {
        $user              = UserTestBuilder::buildWithDefaults();
        $milestone_factory = $this->createMock(Planning_MilestoneFactory::class);
        $project           = ProjectTestBuilder::aProject()->build();
        $planning          = $this->createMock(Planning::class);
        $virtual_milestone = new Planning_VirtualTopMilestone($project, $planning);
        $tracker           = TrackerTestBuilder::aTracker()->withId(3)->build();
        $sub_tracker       = TrackerTestBuilder::aTracker()->withId(4)->build();
        $artifact_factory  = $this->createMock(Tracker_ArtifactFactory::class);
        $title_field       = TrackerFormElementTextFieldBuilder::aTextField(301)->build();
        $start_field       = TrackerFormElementDateFieldBuilder::aDateField(302)->build();
        $end_field         = TrackerFormElementDateFieldBuilder::aDateField(303)->build();
        $sub_title_field   = TrackerFormElementTextFieldBuilder::aTextField(304)->build();
        $changeset         = ChangesetTestBuilder::aChangeset('501')->build();
        $sub_changeset     = ChangesetTestBuilder::aChangeset('502')->build();
        $title_value       = $this->createMock(Tracker_Artifact_ChangesetValue_Text::class);
        $sub_title_value   = $this->createMock(Tracker_Artifact_ChangesetValue_Text::class);
        $start_field->setUserCanRead($user, true);
        $end_field->setUserCanRead($user, true);
        $changeset->setFieldValue($title_field, $title_value);
        $changeset->setFieldValue($start_field, ChangesetValueDateTestBuilder::aValue(
            1,
            $changeset,
            $start_field
        )->withTimestamp((new DateTime('-1day'))->getTimestamp())->build());
        $changeset->setFieldValue($end_field, ChangesetValueDateTestBuilder::aValue(
            2,
            $changeset,
            $end_field
        )->withTimestamp((new DateTime('+1day'))->getTimestamp())->build());
        $sub_changeset->setFieldValue($sub_title_field, $sub_title_value);
        $sub_changeset->setFieldValue($start_field, ChangesetValueDateTestBuilder::aValue(
            1,
            $sub_changeset,
            $start_field
        )->withTimestamp((new DateTime('-1day'))->getTimestamp())->build());
        $sub_changeset->setFieldValue($end_field, ChangesetValueDateTestBuilder::aValue(
            2,
            $sub_changeset,
            $end_field
        )->withTimestamp((new DateTime('+1hour'))->getTimestamp())->build());
        $artifact     = ArtifactTestBuilder::anArtifact(5)
            ->userCanView(true)
            ->inTracker($tracker)
            ->withChangesets($changeset)
            ->withTitle('Title')
            ->build();
        $sub_artifact = ArtifactTestBuilder::anArtifact(6)
            ->userCanView(true)
            ->inTracker($sub_tracker)
            ->withChangesets($sub_changeset)
            ->withTitle('Sub Title')
            ->build();
        Tracker_Semantic_Title::setInstance(new Tracker_Semantic_Title($tracker, $title_field), $tracker);
        Tracker_Semantic_Title::setInstance(new Tracker_Semantic_Title($sub_tracker, $sub_title_field), $sub_tracker);
        $timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $planning_factory  = $this->createMock(PlanningFactory::class);
        Tracker_Semantic_Description::setInstance(new Tracker_Semantic_Description($tracker, null), $tracker);
        Tracker_Semantic_Description::setInstance(new Tracker_Semantic_Description($sub_tracker, null), $sub_tracker);


        $milestone_factory->method('getVirtualTopMilestone')->willReturn($virtual_milestone);
        $planning->method('getPlanningTrackerId')->willReturn($tracker->getId());
        $artifact_factory->expects(self::exactly(2))
            ->method('getInstanceFromRow')
            ->willReturnOnConsecutiveCalls($artifact, $sub_artifact);
        $timeframe_builder->method('getSemantic')->withConsecutive([$tracker], [$sub_tracker])->willReturn(new SemanticTimeframe($tracker, new TimeframeWithEndDate(
            $start_field,
            $end_field,
        )));
        $planning_factory->expects(self::exactly(2))->method('getPlanningByPlanningTracker')
            ->withConsecutive([$tracker], [$sub_tracker])->willReturn($planning);
        $planning->method('getId')->willReturn(105);

        $retriever = new AgileDashboardPromotedMilestonesRetriever(
            $milestone_factory,
            RetrieveMilestonesWithSubMilestonesStub::withMilestones([
                $this->aMilestoneArrayWithASubmilestone($tracker->getId(), $artifact->getId(), $sub_tracker->getId(), $sub_artifact->getId()),
            ]),
            $project,
            CheckMilestonesInSidebarStub::withMilestonesInSidebar(),
            $artifact_factory,
            $planning_factory,
            $this->createMock(ScrumForMonoMilestoneChecker::class),
            $timeframe_builder,
            new NullLogger()
        );

        $items = $retriever->getSidebarPromotedMilestones($user);
        self::assertCount(1, $items);
        $item = $items[0];
        self::assertSame('/plugins/agiledashboard/?group_id=101&planning_id=105&action=show&aid=5&pane=planning-v2', $item->href);
        self::assertSame('Title', $item->label);
        self::assertSame('', $item->description);
        self::assertFalse($item->is_active);
        self::assertEmpty($item->quick_link_add);
        self::assertCount(1, $item->items);
        $sub_item = $item->items[0];
        self::assertSame('/plugins/agiledashboard/?group_id=101&planning_id=105&action=show&aid=6&pane=planning-v2', $sub_item->href);
        self::assertSame('Sub Title', $sub_item->label);
        self::assertSame('', $sub_item->description);
        self::assertFalse($sub_item->is_active);
        self::assertEmpty($sub_item->quick_link_add);
    }

    private function aMilestoneArray(int $tracker_id, int $artifact_id): array
    {
        return [
            'parent_id'                       => "$artifact_id",
            'parent_tracker'                  => "$tracker_id",
            'parent_changeset'                => '501',
            'parent_submitted_by'             => '101',
            'parent_submitted_on'             => '1234567890',
            'parent_use_artifact_permissions' => '1',
            'parent_per_tracker_artifact_id'  => '1',
        ];
    }

    private function aMilestonesArray10(int $tracker_id, int $artifact_id): array
    {
        $result = [];
        for ($i = 0; $i < 10; $i++) {
            $id       = $artifact_id + $i;
            $result[] = [
                'parent_id'                       => "$id",
                'parent_tracker'                  => "$tracker_id",
                'parent_changeset'                => '501',
                'parent_submitted_by'             => '101',
                'parent_submitted_on'             => '1234567890',
                'parent_use_artifact_permissions' => '1',
                'parent_per_tracker_artifact_id'  => '1',
            ];
        }

        return $result;
    }

    private function aMilestoneArrayWithASubmilestone(int $tracker_id, int $artifact_id, int $sub_tracker_id, int $sub_artifact_id): array
    {
        return [
            'parent_id'                             => "$artifact_id",
            'parent_tracker'                        => "$tracker_id",
            'parent_changeset'                      => '501',
            'parent_submitted_by'                   => '101',
            'parent_submitted_on'                   => '1234567890',
            'parent_use_artifact_permissions'       => '1',
            'parent_per_tracker_artifact_id'        => '1',
            'submilestone_id'                       => "$sub_artifact_id",
            'submilestone_tracker'                  => "$sub_tracker_id",
            'submilestone_changeset'                => '502',
            'submilestone_submitted_by'             => '101',
            'submilestone_submitted_on'             => '1324567980',
            'submilestone_use_artifact_permissions' => '1',
            'submilestone_per_tracker_artifact_id'  => '1',
        ];
    }
}
