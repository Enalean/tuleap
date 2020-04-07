<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\RemainingEffortValueRetriever;

class AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactoryTest extends TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
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
              $this->artifact_priority_dao]
        )->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testCollectionsAreProperlyInit(): void
    {
        $user      = Mockery::mock(PFUser::class);
        $milestone = Mockery::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getArtifactId')->andReturn(42);

        $backlog                     = Mockery::mock(AgileDashboard_Milestone_Backlog_Backlog::class);
        $descendant_items_collection = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();

        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(10);
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
            $this->onConsecutiveCalls(
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

        $this->assertEquals([8], $open_and_closed_content->getItemIds());

        $todo_content = $this->collection_factory->getTodoCollection(
            $user,
            $milestone,
            $backlog,
            false
        );

        $this->assertEquals([9], $todo_content->getItemIds());

        $done_content = $this->collection_factory->getDoneCollection(
            $user,
            $milestone,
            $backlog,
            false
        );

        $this->assertEquals([10], $done_content->getItemIds());
    }

    public function testSortedCollectionsAreProperlyInit(): void
    {
        $user      = Mockery::mock(PFUser::class);
        $milestone = Mockery::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getArtifactId')->andReturn(42);

        $backlog                     = Mockery::mock(AgileDashboard_Milestone_Backlog_Backlog::class);
        $descendant_items_collection = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();

        $artifact = Mockery::mock(Tracker_Artifact::class);
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
            $this->onConsecutiveCalls(
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
                    'rank'        => 1
                ]
            ]
        );

        $open_inconsistent_collection = $this->collection_factory->getOpenClosedAndInconsistentCollection(
            $user,
            $milestone,
            $backlog,
            false
        );

        $this->assertEquals([9], $open_inconsistent_collection->getItemIds());

        $open_inconsistent_collection = $this->collection_factory->getInconsistentCollection(
            $user,
            $milestone,
            $backlog,
            false
        );

        $this->assertEquals([9], $open_inconsistent_collection->getItemIds());
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
        $this->artifact_factory->shouldReceive('getChildrenCount')->once()->andReturn(0);
        $this->dao->shouldReceive('getArtifactsSemantics')->once()->andReturn([]);

        $top_backlog_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
        $backlog_item           = Mockery::mock(AgileDashboard_Milestone_Backlog_BacklogItem::class);
        $backlog_item->shouldReceive('id')->andReturn(9);
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(8);
        $tracker = Mockery::mock(Tracker::class);
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getId')->once()->andReturn(102);
        $tracker->shouldReceive('getProject')->once()->andReturn($project);
        $artifact->shouldReceive('getTracker')->once()->andReturn($tracker);
        $backlog_item->shouldReceive('getArtifact')->andReturn($artifact);
        $top_backlog_collection->push($backlog_item);

        $this->backlog_item_builder->method('getCollection')->will(
            $this->onConsecutiveCalls(
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

        $this->assertEquals([9], $unassigned_open_collection->getItemIds());
    }

    public function testItRetrievesUnassignedArtifacts(): void
    {
        $all_possible_artifacts = [8, 9];

        $user      = Mockery::mock(PFUser::class);
        $milestone = Mockery::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getArtifactId')->andReturn(42);

        $backlog = Mockery::mock(AgileDashboard_Milestone_Backlog_Backlog::class);

        $this->milestone_factory->shouldReceive('getSubMilestoneIds')->andReturn($all_possible_artifacts);

        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('id')->andReturn(9);
        $artifact->shouldReceive('getId')->andReturn(9);
        $artifact->shouldReceive('setTitle')->once()->withArgs(['title']);
        $artifact->shouldReceive('getStatus')->once()->once();

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getProject')->once()->andReturn($project);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        $top_backlog_collection           = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $top_backlog_collection->push($artifact);

        $backlog->shouldReceive('getUnplannedArtifacts')->once()->andReturn($top_backlog_collection);

        $this->backlog_item_builder->method('getCollection')->will(
            $this->onConsecutiveCalls(
                new AgileDashboard_Milestone_Backlog_BacklogItemCollection()
            )
        );

        $this->artifact_factory->shouldReceive('getParents')->once()->andReturn([]);
        $this->dao->shouldReceive('getArtifactsSemantics')->once()->andReturn(
            [
                ['id' => 9, 'title' => "title", "title_format" => "text", "status" => "open"]
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

        $item_presenter = Mockery::mock(AgileDashboard_BacklogItemPresenter::class);
        $item_presenter->shouldReceive('setStatus')->once();
        $item_presenter->shouldReceive('setInitialEffort')->once();
        $item_presenter->shouldReceive('setRemainingEffort')->once();
        $item_presenter->shouldReceive('getArtifact')->andReturn($artifact);
        $item_presenter->shouldReceive('id')->once()->andReturn(23);

        $this->backlog_item_builder->method('getItem')->will(
            $this->onConsecutiveCalls(
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

        $this->assertEquals([23], $unassigned_collection->getItemIds());
    }

    public function testItDoesNotSetSemanticWhenUserCantReadThem(): void
    {
        $all_possible_artifacts = [8, 9];

        $user      = Mockery::mock(PFUser::class);
        $milestone = Mockery::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getArtifactId')->andReturn(42);

        $backlog = Mockery::mock(AgileDashboard_Milestone_Backlog_Backlog::class);

        $this->milestone_factory->shouldReceive('getSubMilestoneIds')->andReturn($all_possible_artifacts);

        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('id')->andReturn(9);
        $artifact->shouldReceive('getId')->andReturn(9);
        $artifact->shouldReceive('setTitle')->once()->withArgs(['title']);
        $artifact->shouldReceive('getStatus')->once()->once();

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getProject')->once()->andReturn($project);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        $top_backlog_collection           = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $top_backlog_collection->push($artifact);

        $backlog->shouldReceive('getUnplannedArtifacts')->once()->andReturn($top_backlog_collection);

        $this->backlog_item_builder->method('getCollection')->will(
            $this->onConsecutiveCalls(
                new AgileDashboard_Milestone_Backlog_BacklogItemCollection()
            )
        );

        $this->artifact_factory->shouldReceive('getParents')->once()->andReturn([]);
        $this->dao->shouldReceive('getArtifactsSemantics')->once()->andReturn(
            [
                ['id' => 9, 'title' => "title", "title_format" => "text", "status" => "open"]
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

        $item_presenter = Mockery::mock(AgileDashboard_BacklogItemPresenter::class);
        $item_presenter->shouldReceive('setStatus')->once();
        $item_presenter->shouldReceive('setInitialEffort')->once();
        $item_presenter->shouldReceive('setRemainingEffort')->once();
        $item_presenter->shouldReceive('getArtifact')->andReturn($artifact);
        $item_presenter->shouldReceive('id')->once()->andReturn(23);

        $this->backlog_item_builder->method('getItem')->will(
            $this->onConsecutiveCalls(
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

        $this->assertEquals([23], $unassigned_collection->getItemIds());
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
                    ['artifact_id' => 10]
                ]
            );
        $this->artifacts_in_explicit_backlog_dao->shouldReceive('foundRows')->once()->andReturn(2);

        $artifact_9 = Mockery::mock(Tracker_Artifact::class);
        $artifact_9->shouldReceive('getId')->andReturn(9);
        $this->artifact_factory->shouldReceive('getArtifactById')->withArgs([9])->andReturn($artifact_9);

        $artifact_10 = Mockery::mock(Tracker_Artifact::class);
        $artifact_10->shouldReceive('getId')->andReturn(10);
        $this->artifact_factory->shouldReceive('getArtifactById')->withArgs([10])->andReturn($artifact_10);

        $this->artifact_factory->shouldReceive('getParents')->once()->andReturn([]);
        $this->artifact_factory->shouldReceive('getChildrenCount')->once()->andReturn([]);
        $this->dao->shouldReceive('getArtifactsSemantics')->once()->andReturn([]);

        $backlog_item_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
        $backlog_item            = Mockery::mock(AgileDashboard_Milestone_Backlog_BacklogItem::class);
        $backlog_item->shouldReceive('id')->andReturn(9);
        $backlog_item_collection->push($backlog_item);

        $this->backlog_item_builder->method('getCollection')->will(
            $this->onConsecutiveCalls(
                $backlog_item_collection
            )
        );

        $explicit_backlog_collection = $this->collection_factory->getExplicitTopBacklogItems(
            $user,
            $milestone,
            false,
            50,
            0
        );

        $this->assertEquals([9], $explicit_backlog_collection->getItemIds());
    }
}
