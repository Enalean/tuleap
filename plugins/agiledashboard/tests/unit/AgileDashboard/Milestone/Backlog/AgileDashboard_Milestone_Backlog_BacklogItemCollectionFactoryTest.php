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

use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\RemainingEffortValueRetriever;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

class AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactoryTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_PriorityDao
     */
    private $artifact_priority_dao;

    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory
     */
    private $collection_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;
    /**
     * @var AgileDashboard_Milestone_Backlog_IBuildBacklogItemAndBacklogItemCollection|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $backlog_item_builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|RemainingEffortValueRetriever
     */
    private $remaining_effort_value_retriever;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;
    /**
     * @var AgileDashboard_BacklogItemDao|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao                               = Mockery::mock(AgileDashboard_BacklogItemDao::class);
        $this->artifact_factory                  = Mockery::mock(Tracker_ArtifactFactory::class);
        $this->milestone_factory                 = Mockery::mock(Planning_MilestoneFactory::class);
        $this->planning_factory                  = Mockery::mock(PlanningFactory::class);
        $this->backlog_item_builder              = $this->createMock(
            AgileDashboard_Milestone_Backlog_IBuildBacklogItemAndBacklogItemCollection::class
        );
        $this->remaining_effort_value_retriever  = Mockery::mock(RemainingEffortValueRetriever::class);
        $this->artifacts_in_explicit_backlog_dao = Mockery::mock(ArtifactsInExplicitBacklogDao::class);
        $this->artifact_priority_dao             = Mockery::mock(Tracker_Artifact_PriorityDao::class);

        $this->collection_factory = \Mockery::mock(
            AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory::class,
            [ $this->dao,
                $this->artifact_factory,
                $this->milestone_factory,
                $this->planning_factory,
                $this->backlog_item_builder,
                $this->remaining_effort_value_retriever,
                $this->artifacts_in_explicit_backlog_dao,
                $this->artifact_priority_dao,
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testCollectionsAreProperlyInit(): void
    {
        $user      = Mockery::mock(PFUser::class);
        $milestone = Mockery::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getArtifactId')->andReturn(42);

        $backlog                     = Mockery::mock(AgileDashboard_Milestone_Backlog_Backlog::class);
        $descendant_items_collection = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();

        $artifact = $this->mockArtifact(10, TrackerTestBuilder::aTracker()->build());
        $descendant_items_collection->push($artifact);

        $backlog->shouldReceive('getArtifacts')->once()->andReturn(
            $descendant_items_collection
        );

        $open_and_closed_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
        $open_closed_item           = Mockery::mock(AgileDashboard_Milestone_Backlog_BacklogItem::class);
        $open_closed_item->shouldReceive('id')->once()->andReturn(8);
        $open_and_closed_collection->push($open_closed_item);

        $todo_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
        $todo_item       = Mockery::mock(AgileDashboard_Milestone_Backlog_BacklogItem::class);
        $todo_item->shouldReceive('id')->once()->andReturn(9);
        $todo_collection->push($todo_item);

        $done_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
        $done_item       = Mockery::mock(AgileDashboard_Milestone_Backlog_BacklogItem::class);
        $done_item->shouldReceive('id')->once()->andReturn(10);
        $done_collection->push($done_item);

        $this->backlog_item_builder->method('getCollection')->will(
            self::onConsecutiveCalls(
                $open_and_closed_collection,
                new AgileDashboard_Milestone_Backlog_BacklogItemCollection(),
                $todo_collection,
                $done_collection,
                new AgileDashboard_Milestone_Backlog_BacklogItemCollection()
            )
        );

        $this->artifact_factory->shouldReceive('getParents')->once()->andReturn([]);
        $this->dao->shouldReceive('getArtifactsSemantics')->once()->andReturn([]);
        $this->milestone_factory->shouldReceive('getSubMilestoneIds')->once()->andReturn([]);

        $open_and_closed_content = $this->collection_factory->getOpenAndClosedCollection(
            $user,
            $milestone,
            $backlog,
            false
        );

        self::assertEquals([8], $open_and_closed_content->getItemIds());

        $todo_content = $this->collection_factory->getTodoCollection(
            $user,
            $milestone,
            $backlog,
            false
        );

        self::assertEquals([9], $todo_content->getItemIds());

        $done_content = $this->collection_factory->getDoneCollection(
            $user,
            $milestone,
            $backlog,
            false
        );

        self::assertEquals([10], $done_content->getItemIds());
    }

    public function testSortedCollectionsAreProperlyInit(): void
    {
        $user      = Mockery::mock(PFUser::class);
        $milestone = Mockery::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getArtifactId')->andReturn(42);

        $backlog                     = Mockery::mock(AgileDashboard_Milestone_Backlog_Backlog::class);
        $descendant_items_collection = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();

        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(10);
        $descendant_items_collection->push($artifact);

        $backlog->shouldReceive('getArtifacts')->once()->andReturn(
            $descendant_items_collection
        );

        $open_closed_and_inconsistent_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
        $open_closed_inconsistent_item           = Mockery::mock(AgileDashboard_Milestone_Backlog_BacklogItem::class);
        $open_closed_inconsistent_item->shouldReceive('id')->andReturn(9);
        $open_closed_and_inconsistent_collection->push($open_closed_inconsistent_item);

        $inconsistent_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
        $inconsistent_item       = Mockery::mock(AgileDashboard_Milestone_Backlog_BacklogItem::class);
        $inconsistent_item->shouldReceive('id')->andReturn(9);
        $inconsistent_collection->push($inconsistent_item);

        $sorted_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();

        $this->backlog_item_builder->method('getCollection')->will(
            self::onConsecutiveCalls(
                new AgileDashboard_Milestone_Backlog_BacklogItemCollection(),
                $open_closed_and_inconsistent_collection,
                new AgileDashboard_Milestone_Backlog_BacklogItemCollection(),
                new AgileDashboard_Milestone_Backlog_BacklogItemCollection(),
                $inconsistent_collection,
                $sorted_collection
            )
        );

        $this->artifact_factory->shouldReceive('getParents')->once()->andReturn([]);
        $this->dao->shouldReceive('getArtifactsSemantics')->once()->andReturn([]);
        $this->milestone_factory->shouldReceive('getSubMilestoneIds')->once()->andReturn([]);

        $this->artifact_priority_dao->shouldReceive('getGlobalRanks')->once()->andReturn(
            [
                [
                    'artifact_id' => 9,
                    'rank'        => 1,
                ],
            ]
        );

        $open_inconsistent_collection = $this->collection_factory->getOpenClosedAndInconsistentCollection(
            $user,
            $milestone,
            $backlog,
            false
        );

        self::assertEquals([9], $open_inconsistent_collection->getItemIds());

        $open_inconsistent_collection = $this->collection_factory->getInconsistentCollection(
            $user,
            $milestone,
            $backlog,
            false
        );

        self::assertEquals([9], $open_inconsistent_collection->getItemIds());
    }

    public function testItRetrievesUnplannedArtifacts(): void
    {
        $all_possible_artifacts = [8, 9];

        $user      = Mockery::mock(PFUser::class);
        $milestone = Mockery::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getArtifactId')->andReturn(42);

        $backlog = Mockery::mock(AgileDashboard_Milestone_Backlog_Backlog::class);

        $open_unplanned_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
        $open_unplanned_item       = Mockery::mock(AgileDashboard_Milestone_Backlog_BacklogItem::class);
        $open_unplanned_item->shouldReceive('getId')->andReturn(8);
        $open_unplanned_item->shouldReceive('id')->andReturn(8);
        $open_unplanned_collection->push($open_unplanned_item);

        $backlog->shouldReceive('getOpenUnplannedArtifacts')->once()->andReturn($open_unplanned_collection);

        $this->milestone_factory->shouldReceive('getSubMilestoneIds')->once()->andReturn($all_possible_artifacts);

        $this->artifact_factory->shouldReceive('getParents')->once()->andReturn([]);
        $this->artifact_factory->shouldReceive('getChildrenCount')->once()->andReturn([
            9 => 0,
        ]);
        $this->dao->shouldReceive('getArtifactsSemantics')->once()->andReturn([]);

        $top_backlog_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
        $backlog_item           = Mockery::mock(AgileDashboard_Milestone_Backlog_BacklogItem::class);
        $backlog_item->shouldReceive('id')->andReturn(9);
        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(8);
        $tracker = Mockery::mock(Tracker::class);
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getId')->once()->andReturn(102);
        $tracker->shouldReceive('getProject')->once()->andReturn($project);
        $artifact->shouldReceive('getTracker')->once()->andReturn($tracker);
        $backlog_item->shouldReceive('getArtifact')->andReturn($artifact);
        $top_backlog_collection->push($backlog_item);

        $this->backlog_item_builder->method('getCollection')->will(
            self::onConsecutiveCalls(
                $top_backlog_collection,
                new AgileDashboard_Milestone_Backlog_BacklogItemCollection()
            )
        );

        $planning = Mockery::mock(Planning::class);
        $planning->shouldReceive('getPlanningTrackerId')->once()->andReturn(45);
        $this->planning_factory->shouldReceive('getPlannings')->once()->andReturn([$planning]);

        $this->artifact_factory->shouldReceive('getArtifactIdsLinkedToTrackers')->once()->andReturn([8]);

        $unassigned_open_collection = $this->collection_factory->getUnassignedOpenCollection(
            $user,
            $milestone,
            $backlog,
            false
        );

        self::assertEquals([9], $unassigned_open_collection->getItemIds());
    }

    public function testItRetrievesUnassignedArtifacts(): void
    {
        $all_possible_artifacts = [8, 9];

        $user      = Mockery::mock(PFUser::class);
        $milestone = Mockery::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getArtifactId')->andReturn(42);

        $backlog = Mockery::mock(AgileDashboard_Milestone_Backlog_Backlog::class);

        $this->milestone_factory->shouldReceive('getSubMilestoneIds')->andReturn($all_possible_artifacts);

        $project = Project::buildForTest();

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getProject')->once()->andReturn($project);

        $artifact = $this->mockArtifact(9, $tracker);
        $artifact->shouldReceive('setTitle')->once()->withArgs(['title']);
        $artifact->shouldReceive('getStatus')->once();

        $top_backlog_collection = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $top_backlog_collection->push($artifact);

        $backlog->shouldReceive('getUnplannedArtifacts')->once()->andReturn($top_backlog_collection);

        $this->backlog_item_builder->method('getCollection')->will(
            self::onConsecutiveCalls(
                new AgileDashboard_Milestone_Backlog_BacklogItemCollection()
            )
        );

        $this->artifact_factory->shouldReceive('getParents')->once()->andReturn([]);
        $this->dao->shouldReceive('getArtifactsSemantics')->once()->andReturn(
            [
                ['id' => 9, 'title' => "title", "title_format" => "text", "status" => "open"],
            ]
        );
        $this->collection_factory->shouldReceive('userCanReadBacklogTitleField')->once()->andReturnTrue();
        $this->collection_factory->shouldReceive('userCanReadBacklogStatusField')->once()->andReturnTrue();

        $field = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $field->shouldReceive('getComputedValue')->andReturn(65);
        $this->collection_factory->shouldReceive('getInitialEffortField')->once()->andReturn($field);
        $this->collection_factory->shouldReceive('userCanReadInitialEffortField')->once()->andReturnTrue();

        $this->artifact_factory->shouldReceive('getTitleFromRowAsText')->once()->andReturn('title');

        $this->remaining_effort_value_retriever->shouldReceive('getRemainingEffortValue')->once()->andReturn(12.6);

        $item_presenter = $this->mockItemPresenter($artifact, 23);

        $this->backlog_item_builder->method('getItem')->will(
            self::onConsecutiveCalls(
                $item_presenter
            )
        );

        $this->planning_factory->shouldReceive('getPlannings')->once()->andReturn([]);

        $unassigned_collection = $this->collection_factory->getUnassignedCollection(
            $user,
            $milestone,
            $backlog,
            false
        );

        self::assertEquals([23], $unassigned_collection->getItemIds());
    }

    public function testItDoesNotSetSemanticWhenUserCantReadThem(): void
    {
        $all_possible_artifacts = [8, 9];

        $user      = Mockery::mock(PFUser::class);
        $milestone = Mockery::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getArtifactId')->andReturn(42);

        $backlog = Mockery::mock(AgileDashboard_Milestone_Backlog_Backlog::class);

        $this->milestone_factory->shouldReceive('getSubMilestoneIds')->andReturn($all_possible_artifacts);

        $project = Project::buildForTest();
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getProject')->once()->andReturn($project);

        $artifact = $this->mockArtifact(9, $tracker);
        $artifact->shouldReceive('setTitle')->once()->withArgs(['title']);
        $artifact->shouldReceive('getStatus')->once();

        $top_backlog_collection = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $top_backlog_collection->push($artifact);

        $backlog->shouldReceive('getUnplannedArtifacts')->once()->andReturn($top_backlog_collection);

        $this->backlog_item_builder->method('getCollection')->will(
            self::onConsecutiveCalls(
                new AgileDashboard_Milestone_Backlog_BacklogItemCollection()
            )
        );

        $this->artifact_factory->shouldReceive('getParents')->once()->andReturn([]);
        $this->dao->shouldReceive('getArtifactsSemantics')->once()->andReturn(
            [
                ['id' => 9, 'title' => "title", "title_format" => "text", "status" => "open"],
            ]
        );
        $this->collection_factory->shouldReceive('userCanReadBacklogTitleField')->once()->andReturnFalse();
        $this->collection_factory->shouldReceive('userCanReadBacklogStatusField')->once()->andReturnFalse();

        $field = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $field->shouldReceive('getComputedValue')->andReturn(65);
        $this->collection_factory->shouldReceive('getInitialEffortField')->never();
        $this->collection_factory->shouldReceive('userCanReadInitialEffortField')->once()->andReturnFalse();

        $this->artifact_factory->shouldReceive('getTitleFromRowAsText')->once()->andReturn('title');

        $this->remaining_effort_value_retriever->shouldReceive('getRemainingEffortValue')->once()->andReturn(12.6);

        $item_presenter = $this->mockItemPresenter($artifact, 23);

        $this->backlog_item_builder->method('getItem')->will(
            self::onConsecutiveCalls(
                $item_presenter
            )
        );

        $this->planning_factory->shouldReceive('getPlannings')->once()->andReturn([]);

        $unassigned_collection = $this->collection_factory->getUnassignedCollection(
            $user,
            $milestone,
            $backlog,
            false
        );

        self::assertEquals([23], $unassigned_collection->getItemIds());
    }

    public function testItBuildExplicitBacklogCollection(): void
    {
        $user      = Mockery::mock(PFUser::class);
        $milestone = Mockery::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getGroupId')->once()->andReturn(101);

        $this->artifacts_in_explicit_backlog_dao->shouldReceive('getTopBacklogItemsForProjectSortedByRank')
            ->once()
            ->andReturn(
                [
                    ['artifact_id' => 9],
                    ['artifact_id' => 10],
                ]
            );
        $this->artifacts_in_explicit_backlog_dao->shouldReceive('foundRows')->once()->andReturn(2);

        $artifact_9 = $this->mockArtifact(9, TrackerTestBuilder::aTracker()->build());
        $this->artifact_factory->shouldReceive('getArtifactById')->withArgs([9])->andReturn($artifact_9);

        $artifact_10 = $this->mockArtifact(10, TrackerTestBuilder::aTracker()->build());
        $this->artifact_factory->shouldReceive('getArtifactById')->withArgs([10])->andReturn($artifact_10);

        $this->artifact_factory->shouldReceive('getParents')->once()->andReturn([]);
        $this->artifact_factory->shouldReceive('getChildrenCount')->once()->andReturn([]);
        $this->dao->shouldReceive('getArtifactsSemantics')->once()->andReturn([]);

        $backlog_item_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
        $backlog_item            = Mockery::mock(AgileDashboard_Milestone_Backlog_BacklogItem::class);
        $backlog_item->shouldReceive('id')->andReturn(9);
        $backlog_item_collection->push($backlog_item);

        $this->backlog_item_builder->method('getCollection')->will(
            self::onConsecutiveCalls(
                $backlog_item_collection
            )
        );

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
        $milestone = Mockery::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getArtifactId')->andReturn(1);

        $backlog = Mockery::mock(AgileDashboard_Milestone_Backlog_Backlog::class);

        $backlog_item_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();

        $parent_can_be_seen = $this->mockArtifact(555, TrackerTestBuilder::aTracker()->build());
        $parent_can_be_seen->shouldReceive('userCanView')->andReturn(true);

        $parent_cannot_be_seen = Mockery::mock(Artifact::class);
        $parent_cannot_be_seen->shouldReceive('userCanView')->andReturn(false);

        $this->backlog_item_builder->method('getCollection')->will(
            self::onConsecutiveCalls(
                $backlog_item_collection,
                $backlog_item_collection,
                $backlog_item_collection,
                $backlog_item_collection,
                $backlog_item_collection,
                $backlog_item_collection,
            )
        );

        $this->remaining_effort_value_retriever->shouldReceive('getRemainingEffortValue')->times(4)->andReturn(12.6);

        $items_collection = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();

        $artifact_10 = $this->mockArtifact(10, TrackerTestBuilder::aTracker()->build());
        $artifact_10->shouldReceive('setTitle');
        $artifact_10->shouldReceive('getStatus');
        $items_collection->push($artifact_10);

        $item_presenter_artifact_10 = $this->mockItemPresenter($artifact_10, 10);
        $item_presenter_artifact_10->shouldReceive('setParent')->twice()->with($parent_can_be_seen);

        $artifact_11 = $this->mockArtifact(11, TrackerTestBuilder::aTracker()->build());
        $artifact_11->shouldReceive('setTitle');
        $artifact_11->shouldReceive('getStatus');
        $items_collection->push($artifact_11);

        $item_presenter_artifact_11 = $this->mockItemPresenter($artifact_11, 11);
        $item_presenter_artifact_11->shouldReceive('setParent')->never();

        $top_backlog_collection = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $top_backlog_collection->push($artifact_10);
        $top_backlog_collection->push($artifact_11);

        $backlog->shouldReceive('getUnplannedArtifacts')->once()->andReturn($top_backlog_collection);

        $this->backlog_item_builder->method('getItem')->will(
            self::onConsecutiveCalls(
                $item_presenter_artifact_10,
                $item_presenter_artifact_11,
                $item_presenter_artifact_10,
                $item_presenter_artifact_11,
            )
        );

        $backlog->shouldReceive('getArtifacts')->once()->andReturn(
            $items_collection
        );

        $this->artifact_factory->shouldReceive('getParents')->twice()->andReturn([10 => $parent_can_be_seen, 11 => $parent_cannot_be_seen]);
        $this->artifact_factory->shouldReceive('setTitles')->twice();
        $this->artifact_factory->shouldReceive('getTitleFromRowAsText')->times(4);

        $this->dao->shouldReceive('getArtifactsSemantics')->twice()->andReturn(
            [
                ['id' => 10, 'title' => "title", "title_format" => "text", "status" => "open"],
                ['id' => 11, 'title' => "title", "title_format" => "text", "status" => "open"],
            ]
        );
        $this->collection_factory->shouldReceive('getInitialEffortField')->times(4)->andReturnNull();
        $this->collection_factory->shouldReceive('userCanReadBacklogTitleField')->times(6)->andReturnTrue();
        $this->collection_factory->shouldReceive('userCanReadBacklogStatusField')->times(4)->andReturnTrue();

        $this->milestone_factory->shouldReceive('getSubMilestoneIds')->twice()->andReturn([]);

        $collection = $this->collection_factory->getOpenAndClosedCollection(
            $user,
            $milestone,
            $backlog,
            false
        );

        self::assertSame([10, 11], $collection->getItemIds());

        $collection = $this->collection_factory->getUnplannedCollection(
            $user,
            $milestone,
            $backlog,
            false
        );

        self::assertSame([10, 11], $collection->getItemIds());
    }

    /**
     * @param \Mockery\LegacyMockInterface|\Mockery\MockInterface|Artifact $artifact
     * @param int $id
     * @return AgileDashboard_BacklogItemPresenter|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private function mockItemPresenter($artifact, $id)
    {
        $presenter = Mockery::mock(AgileDashboard_BacklogItemPresenter::class);
        $presenter->shouldReceive('setStatus');
        $presenter->shouldReceive('setInitialEffort');
        $presenter->shouldReceive('setRemainingEffort');
        $presenter->shouldReceive('getArtifact')->andReturn($artifact);
        $presenter->shouldReceive('id')->andReturn($id);

        return $presenter;
    }

    /**
     * @param \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker $tracker
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|Artifact
     */
    private function mockArtifact(int $id, $tracker)
    {
        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getId')->andReturn($id);
        $artifact->shouldReceive('id')->andReturn($id);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        return $artifact;
    }
}
