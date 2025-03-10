<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Milestone\Backlog;

use AgileDashboard_Milestone_Backlog_Backlog;
use AgileDashboard_Milestone_Backlog_BacklogItemCollection;
use AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory;
use AgileDashboard_Milestone_Backlog_DescendantItemsCollection;
use AgileDashboard_Milestone_Backlog_IBuildBacklogItemAndBacklogItemCollection;
use PHPUnit\Framework\MockObject\MockObject;
use Planning_Milestone;
use Planning_MilestoneFactory;
use PlanningFactory;
use Tracker_Artifact_PriorityDao;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_Integer;
use Tuleap\AgileDashboard\BacklogItemDao;
use Tuleap\AgileDashboard\BacklogItemPresenter;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\RemainingEffortValueRetriever;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Permission\TrackersPermissionsPassthroughRetriever;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactoryTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Tracker_Artifact_PriorityDao&MockObject $artifact_priority_dao;
    private AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory&MockObject $collection_factory;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private Planning_MilestoneFactory&MockObject $milestone_factory;
    private PlanningFactory&MockObject $planning_factory;
    private AgileDashboard_Milestone_Backlog_IBuildBacklogItemAndBacklogItemCollection&MockObject $backlog_item_builder;
    private RemainingEffortValueRetriever&MockObject $remaining_effort_value_retriever;
    private ArtifactsInExplicitBacklogDao&MockObject $artifacts_in_explicit_backlog_dao;
    private BacklogItemDao&MockObject $dao;

    protected function setUp(): void
    {
        $this->dao                               = $this->createMock(BacklogItemDao::class);
        $this->artifact_factory                  = $this->createMock(Tracker_ArtifactFactory::class);
        $this->milestone_factory                 = $this->createMock(Planning_MilestoneFactory::class);
        $this->planning_factory                  = $this->createMock(PlanningFactory::class);
        $this->backlog_item_builder              = $this->createMock(
            AgileDashboard_Milestone_Backlog_IBuildBacklogItemAndBacklogItemCollection::class
        );
        $this->remaining_effort_value_retriever  = $this->createMock(RemainingEffortValueRetriever::class);
        $this->artifacts_in_explicit_backlog_dao = $this->createMock(ArtifactsInExplicitBacklogDao::class);
        $this->artifact_priority_dao             = $this->createMock(Tracker_Artifact_PriorityDao::class);

        $this->collection_factory = $this->getMockBuilder(AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory::class)
            ->setConstructorArgs([
                $this->dao,
                $this->artifact_factory,
                $this->milestone_factory,
                $this->planning_factory,
                $this->backlog_item_builder,
                $this->remaining_effort_value_retriever,
                $this->artifacts_in_explicit_backlog_dao,
                $this->artifact_priority_dao,
                new TrackersPermissionsPassthroughRetriever(),
            ])
            ->onlyMethods([
                'userCanReadBacklogTitleField',
                'userCanReadBacklogStatusField',
                'userCanReadInitialEffortField',
                'getInitialEffortField',
            ])
            ->getMock();
    }

    public function testCollectionsAreProperlyInit(): void
    {
        $user      = UserTestBuilder::buildWithDefaults();
        $milestone = $this->createMock(Planning_Milestone::class);
        $milestone->method('getArtifactId')->willReturn(42);

        $backlog                     = $this->createMock(AgileDashboard_Milestone_Backlog_Backlog::class);
        $descendant_items_collection = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();

        $artifact = ArtifactTestBuilder::anArtifact(10)
            ->inTracker(TrackerTestBuilder::aTracker()->build())
            ->build();
        $descendant_items_collection->push($artifact);

        $backlog->expects(self::once())->method('getArtifacts')->willReturn($descendant_items_collection);

        $open_and_closed_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
        $open_closed_item           = new BacklogItem(ArtifactTestBuilder::anArtifact(8)->build(), false);
        $open_and_closed_collection->push($open_closed_item);

        $todo_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
        $todo_item       = new BacklogItem(ArtifactTestBuilder::anArtifact(9)->build(), false);
        $todo_collection->push($todo_item);

        $done_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
        $done_item       = new BacklogItem(ArtifactTestBuilder::anArtifact(10)->build(), false);
        $done_collection->push($done_item);

        $this->backlog_item_builder->method('getCollection')->willReturnOnConsecutiveCalls(
            $open_and_closed_collection,
            new AgileDashboard_Milestone_Backlog_BacklogItemCollection(),
            $todo_collection,
            $done_collection,
            new AgileDashboard_Milestone_Backlog_BacklogItemCollection(),
        );

        $this->artifact_factory->expects(self::once())->method('getParents')->willReturn([]);
        $this->dao->expects(self::once())->method('getArtifactsSemantics')->willReturn([]);
        $this->milestone_factory->expects(self::once())->method('getSubMilestoneIds')->willReturn([]);

        $open_and_closed_content = $this->collection_factory->getOpenAndClosedCollection(
            $user,
            $milestone,
            $backlog,
            null,
        );

        self::assertEquals([8], $open_and_closed_content->getItemIds());

        $todo_content = $this->collection_factory->getTodoCollection(
            $user,
            $milestone,
            $backlog,
            null,
        );

        self::assertEquals([9], $todo_content->getItemIds());

        $done_content = $this->collection_factory->getDoneCollection(
            $user,
            $milestone,
            $backlog,
            null,
        );

        self::assertEquals([10], $done_content->getItemIds());
    }

    public function testSortedCollectionsAreProperlyInit(): void
    {
        $user      = UserTestBuilder::buildWithDefaults();
        $milestone = $this->createMock(Planning_Milestone::class);
        $milestone->method('getArtifactId')->willReturn(42);

        $backlog                     = $this->createMock(AgileDashboard_Milestone_Backlog_Backlog::class);
        $descendant_items_collection = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();

        $artifact = ArtifactTestBuilder::anArtifact(10)->build();
        $descendant_items_collection->push($artifact);
        $descendant_items_collection->setTotalAvaialableSize(1);

        $backlog->expects(self::once())->method('getArtifacts')->willReturn($descendant_items_collection);

        $open_closed_and_inconsistent_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
        $open_closed_inconsistent_item           = new BacklogItem(ArtifactTestBuilder::anArtifact(9)->build(), true);
        $open_closed_and_inconsistent_collection->push($open_closed_inconsistent_item);

        $inconsistent_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
        $inconsistent_item       = new BacklogItem(ArtifactTestBuilder::anArtifact(9)->build(), true);
        $inconsistent_collection->push($inconsistent_item);
        $inconsistent_collection->setTotalAvaialableSize(1);

        $sorted_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();

        $this->backlog_item_builder->method('getCollection')->willReturnOnConsecutiveCalls(
            new AgileDashboard_Milestone_Backlog_BacklogItemCollection(),
            $open_closed_and_inconsistent_collection,
            new AgileDashboard_Milestone_Backlog_BacklogItemCollection(),
            new AgileDashboard_Milestone_Backlog_BacklogItemCollection(),
            $inconsistent_collection,
            $sorted_collection
        );

        $this->artifact_factory->expects(self::once())->method('getParents')->willReturn([]);
        $this->dao->expects(self::once())->method('getArtifactsSemantics')->willReturn([]);
        $this->milestone_factory->expects(self::once())->method('getSubMilestoneIds')->willReturn([]);

        $this->artifact_priority_dao->expects(self::once())->method('getGlobalRanks')->willReturn([
            [
                'artifact_id' => 9,
                'rank'        => 1,
            ],
        ]);

        $open_inconsistent_collection = $this->collection_factory->getOpenClosedAndInconsistentCollection(
            $user,
            $milestone,
            $backlog,
            null,
        );

        self::assertEquals([9], $open_inconsistent_collection->getItemIds());

        $open_inconsistent_collection = $this->collection_factory->getInconsistentCollection(
            $user,
            $milestone,
            $backlog,
            null,
        );

        self::assertEquals([9], $open_inconsistent_collection->getItemIds());
    }

    public function testItRetrievesUnplannedArtifacts(): void
    {
        $all_possible_artifacts = [8, 9];

        $user      = UserTestBuilder::buildWithDefaults();
        $milestone = $this->createMock(Planning_Milestone::class);
        $milestone->method('getArtifactId')->willReturn(42);

        $backlog = $this->createMock(AgileDashboard_Milestone_Backlog_Backlog::class);

        $open_unplanned_collection = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $open_unplanned_collection->push(ArtifactTestBuilder::anArtifact(8)->build());

        $backlog->expects(self::once())->method('getOpenUnplannedArtifacts')->willReturn($open_unplanned_collection);

        $this->milestone_factory->expects(self::once())->method('getSubMilestoneIds')->willReturn($all_possible_artifacts);

        $this->artifact_factory->expects(self::once())->method('getParents')->willReturn([]);
        $this->artifact_factory->expects(self::once())->method('getChildrenCount')->willReturn([9 => 0]);
        $this->dao->expects(self::once())->method('getArtifactsSemantics')->willReturn([]);

        $top_backlog_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
        $project                = ProjectTestBuilder::aProject()->withId(102)->build();
        $tracker                = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $artifact               = ArtifactTestBuilder::anArtifact(9)->inTracker($tracker)->build();
        $backlog_item           = new BacklogItem($artifact, false);
        $top_backlog_collection->push($backlog_item);

        $this->backlog_item_builder->method('getCollection')->willReturnOnConsecutiveCalls(
            $top_backlog_collection,
            new AgileDashboard_Milestone_Backlog_BacklogItemCollection(),
        );

        $planning = PlanningBuilder::aPlanning(101)
            ->withMilestoneTracker(TrackerTestBuilder::aTracker()->withId(45)->build())
            ->build();
        $this->planning_factory->expects(self::once())->method('getPlannings')->willReturn([$planning]);

        $this->artifact_factory->expects(self::once())->method('getArtifactIdsLinkedToTrackers')->willReturn([8]);

        $unassigned_open_collection = $this->collection_factory->getUnassignedOpenCollection(
            $user,
            $milestone,
            $backlog,
            null,
        );

        self::assertEquals([9], $unassigned_open_collection->getItemIds());
    }

    public function testItRetrievesUnassignedArtifacts(): void
    {
        $all_possible_artifacts = [8, 9];

        $user      = UserTestBuilder::buildWithDefaults();
        $milestone = $this->createMock(Planning_Milestone::class);
        $milestone->method('getArtifactId')->willReturn(42);

        $backlog = $this->createMock(AgileDashboard_Milestone_Backlog_Backlog::class);

        $this->milestone_factory->method('getSubMilestoneIds')->willReturn($all_possible_artifacts);

        $project  = ProjectTestBuilder::aProject()->build();
        $tracker  = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $artifact = ArtifactTestBuilder::anArtifact(23)->inTracker($tracker)->withStatus('')->build();

        $top_backlog_collection = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $top_backlog_collection->push($artifact);

        $backlog->expects(self::once())->method('getUnplannedArtifacts')->willReturn($top_backlog_collection);

        $this->backlog_item_builder->method('getCollection')->willReturn(new AgileDashboard_Milestone_Backlog_BacklogItemCollection());

        $this->artifact_factory->expects(self::once())->method('getParents')->willReturn([]);
        $this->dao->expects(self::once())->method('getArtifactsSemantics')->willReturn([
            ['id' => 23, 'title' => 'title', 'title_format' => 'text', 'status' => 'open'],
        ]);
        $this->collection_factory->expects(self::once())->method('userCanReadBacklogTitleField')->willReturn(true);
        $this->collection_factory->expects(self::once())->method('userCanReadBacklogStatusField')->willReturn(true);

        $field = $this->createMock(Tracker_FormElement_Field_Integer::class);
        $field->method('getComputedValue')->willReturn(65);
        $this->collection_factory->expects(self::once())->method('getInitialEffortField')->willReturn($field);
        $this->collection_factory->expects(self::once())->method('userCanReadInitialEffortField')->willReturn(true);

        $this->artifact_factory->expects(self::once())->method('getTitleFromRowAsText')->willReturn('title');

        $this->remaining_effort_value_retriever->expects(self::once())->method('getRemainingEffortValue')->willReturn(12.6);

        $item_presenter = $this->getItemPresenter($artifact);

        $this->backlog_item_builder->method('getItem')->willReturn($item_presenter);

        $this->planning_factory->expects(self::once())->method('getPlannings')->willReturn([]);

        $unassigned_collection = $this->collection_factory->getUnassignedCollection(
            $user,
            $milestone,
            $backlog,
            null,
        );

        self::assertEquals([23], $unassigned_collection->getItemIds());
    }

    public function testItDoesNotSetSemanticWhenUserCantReadThem(): void
    {
        $all_possible_artifacts = [8, 9];

        $user      = UserTestBuilder::buildWithDefaults();
        $milestone = $this->createMock(Planning_Milestone::class);
        $milestone->method('getArtifactId')->willReturn(42);

        $backlog = $this->createMock(AgileDashboard_Milestone_Backlog_Backlog::class);

        $this->milestone_factory->method('getSubMilestoneIds')->willReturn($all_possible_artifacts);

        $project  = ProjectTestBuilder::aProject()->build();
        $tracker  = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $artifact = ArtifactTestBuilder::anArtifact(23)->inTracker($tracker)->withStatus('')->build();

        $top_backlog_collection = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $top_backlog_collection->push($artifact);

        $backlog->expects(self::once())->method('getUnplannedArtifacts')->willReturn($top_backlog_collection);

        $this->backlog_item_builder->method('getCollection')->willReturn(new AgileDashboard_Milestone_Backlog_BacklogItemCollection());

        $this->artifact_factory->expects(self::once())->method('getParents')->willReturn([]);
        $this->dao->expects(self::once())->method('getArtifactsSemantics')->willReturn([
            ['id' => 23, 'title' => 'title', 'title_format' => 'text', 'status' => 'open'],
        ]);
        $this->collection_factory->expects(self::once())->method('userCanReadBacklogTitleField')->willReturn(false);
        $this->collection_factory->expects(self::once())->method('userCanReadBacklogStatusField')->willReturn(false);

        $field = $this->createMock(Tracker_FormElement_Field_Integer::class);
        $field->method('getComputedValue')->willReturn(65);
        $this->collection_factory->expects(self::never())->method('getInitialEffortField');
        $this->collection_factory->expects(self::once())->method('userCanReadInitialEffortField')->willReturn(false);

        $this->artifact_factory->expects(self::once())->method('getTitleFromRowAsText')->willReturn('title');

        $this->remaining_effort_value_retriever->expects(self::once())->method('getRemainingEffortValue')->willReturn(12.6);

        $item_presenter = $this->getItemPresenter($artifact);

        $this->backlog_item_builder->method('getItem')->willReturn($item_presenter);

        $this->planning_factory->expects(self::once())->method('getPlannings')->willReturn([]);

        $unassigned_collection = $this->collection_factory->getUnassignedCollection(
            $user,
            $milestone,
            $backlog,
            null,
        );

        self::assertEquals([23], $unassigned_collection->getItemIds());
    }

    public function testItBuildExplicitBacklogCollection(): void
    {
        $user      = UserTestBuilder::buildWithDefaults();
        $milestone = $this->createMock(Planning_Milestone::class);
        $milestone->expects(self::once())->method('getGroupId')->willReturn(101);

        $this->artifacts_in_explicit_backlog_dao->expects(self::once())->method('getOpenTopBacklogItemsForProjectSortedByRank')
            ->willReturn([
                ['artifact_id' => 9],
                ['artifact_id' => 10],
            ]);
        $this->artifacts_in_explicit_backlog_dao->expects(self::once())->method('foundRows')->willReturn(2);

        $artifact_9  = ArtifactTestBuilder::anArtifact(9)->inTracker(TrackerTestBuilder::aTracker()->build())->build();
        $artifact_10 = ArtifactTestBuilder::anArtifact(10)->inTracker(TrackerTestBuilder::aTracker()->build())->build();
        $matcher     = $this->exactly(2);
        $this->artifact_factory->expects($matcher)->method('getArtifactById')->willReturnCallback(function (...$parameters) use ($matcher, $artifact_9, $artifact_10) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame(9, $parameters[0]);
                return $artifact_9;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame(10, $parameters[0]);
                return $artifact_10;
            }
        });

        $this->artifact_factory->expects(self::once())->method('getParents')->willReturn([]);
        $this->artifact_factory->expects(self::once())->method('getChildrenCount')->willReturn([]);
        $this->dao->expects(self::once())->method('getArtifactsSemantics')->willReturn([]);

        $backlog_item_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
        $backlog_item            = new BacklogItem($artifact_9, false);
        $backlog_item_collection->push($backlog_item);

        $this->backlog_item_builder->method('getCollection')->willReturn($backlog_item_collection);

        $explicit_backlog_collection = $this->collection_factory->getExplicitTopBacklogItems(
            $user,
            $milestone,
            null,
            50,
            0
        );

        self::assertEquals([9], $explicit_backlog_collection->getItemIds());
    }

    public function testSetOnlyParentThatUserCanSee(): void
    {
        $user      = UserTestBuilder::aUser()->build();
        $milestone = $this->createMock(Planning_Milestone::class);
        $milestone->method('getArtifactId')->willReturn(1);

        $backlog = $this->createMock(AgileDashboard_Milestone_Backlog_Backlog::class);

        $backlog_item_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();

        $parent_can_be_seen = ArtifactTestBuilder::anArtifact(555)
            ->inTracker(TrackerTestBuilder::aTracker()->build())
            ->userCanView($user)
            ->build();

        $parent_cannot_be_seen = ArtifactTestBuilder::anArtifact(1)
            ->userCannotView($user)
            ->build();

        $this->backlog_item_builder->expects(self::exactly(6))->method('getCollection')->willReturn($backlog_item_collection);

        $this->remaining_effort_value_retriever->expects(self::exactly(4))->method('getRemainingEffortValue')->willReturn(12.6);

        $items_collection = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();

        $artifact_10 = ArtifactTestBuilder::anArtifact(10)->inTracker(TrackerTestBuilder::aTracker()->build())->withStatus('')->build();
        $items_collection->push($artifact_10);

        $item_presenter_artifact_10 = $this->getItemPresenter($artifact_10);

        $artifact_11 = ArtifactTestBuilder::anArtifact(11)->inTracker(TrackerTestBuilder::aTracker()->build())->withStatus('')->build();
        $items_collection->push($artifact_11);

        $item_presenter_artifact_11 = $this->getItemPresenter($artifact_11);

        $top_backlog_collection = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $top_backlog_collection->push($artifact_10);
        $top_backlog_collection->push($artifact_11);

        $backlog->expects(self::once())->method('getUnplannedArtifacts')->willReturn($top_backlog_collection);

        $this->backlog_item_builder->expects(self::exactly(4))->method('getItem')->willReturnOnConsecutiveCalls(
            $item_presenter_artifact_10,
            $item_presenter_artifact_11,
            $item_presenter_artifact_10,
            $item_presenter_artifact_11,
        );

        $backlog->expects(self::once())->method('getArtifacts')->willReturn($items_collection);

        $this->artifact_factory->expects(self::exactly(2))->method('getParents')->willReturn([10 => $parent_can_be_seen, 11 => $parent_cannot_be_seen]);
        $this->artifact_factory->expects(self::exactly(2))->method('setTitles');
        $this->artifact_factory->expects(self::exactly(4))->method('getTitleFromRowAsText');

        $this->dao->expects(self::exactly(2))->method('getArtifactsSemantics')->willReturn([
            ['id' => 10, 'title' => 'title', 'title_format' => 'text', 'status' => 'open'],
            ['id' => 11, 'title' => 'title', 'title_format' => 'text', 'status' => 'open'],
        ]);
        $this->collection_factory->expects(self::exactly(4))->method('getInitialEffortField')->willReturn(null);
        $this->collection_factory->expects(self::exactly(6))->method('userCanReadBacklogTitleField')->willReturn(true);
        $this->collection_factory->expects(self::exactly(4))->method('userCanReadBacklogStatusField')->willReturn(true);
        $this->collection_factory->expects(self::exactly(4))->method('userCanReadInitialEffortField')->willReturn(true);

        $this->milestone_factory->expects(self::exactly(2))->method('getSubMilestoneIds')->willReturn([]);

        $collection = $this->collection_factory->getOpenAndClosedCollection(
            $user,
            $milestone,
            $backlog,
            null,
        );

        self::assertSame([10, 11], $collection->getItemIds());

        $collection = $this->collection_factory->getUnplannedCollection(
            $user,
            $milestone,
            $backlog,
            null,
        );

        self::assertSame([10, 11], $collection->getItemIds());
    }

    private function getItemPresenter(Artifact $artifact): BacklogItemPresenter
    {
        return new BacklogItemPresenter(
            $artifact,
            '',
            false,
        );
    }
}
