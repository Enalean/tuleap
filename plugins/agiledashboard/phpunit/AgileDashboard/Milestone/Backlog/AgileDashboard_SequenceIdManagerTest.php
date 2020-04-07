<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Backlog;

use AgileDashboard_Milestone_Backlog_Backlog;
use AgileDashboard_Milestone_Backlog_BacklogFactory;
use AgileDashboard_Milestone_Backlog_BacklogItemCollection;
use AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory;
use AgileDashboard_Milestone_Backlog_DescendantItemsCollection;
use AgileDashboard_SequenceIdManager;
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;

final class AgileDashboard_SequenceIdManagerTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|null
     */
    private $virtual_top_milestone;
    /**
     * @var int
     */
    private $artifact_id_1;
    /**
     * @var int
     */
    private $artifact_id_2;
    /**
     * @var int
     */
    private $artifact_id_3;
    /**
     * @var int
     */
    private $artifact_id_6;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact_1;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact_2;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact_3;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact_4;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact_5;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact_6;
    /**
     * @var AgileDashboard_SequenceIdManager
     */
    private $sequence_id_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|null
     */
    private $milestone_1;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|null
     */
    private $milestone_2;
    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $backlog_factory;
    /**
     * @var AgileDashboard_Milestone_Backlog_Backlog|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $backlog_1;
    /**
     * @var AgileDashboard_Milestone_Backlog_Backlog|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $backlog_2;
    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $backlog_item_collection_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|null
     */
    private $backlog_item_1;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|null
     */
    private $backlog_item_2;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|null
     */
    private $backlog_item_3;
    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogItemCollection
     */
    private $items_collection;

    protected function setUp(): void
    {
        $milestone_1_id    = 132;
        $this->milestone_1 = Mockery::spy(\Planning_ArtifactMilestone::class)
            ->shouldReceive('getArtifactId')->andReturns($milestone_1_id)->getMock();

        $milestone_2_id    = 853;
        $this->milestone_2 = Mockery::spy(\Planning_ArtifactMilestone::class)
            ->shouldReceive('getArtifactId')->andReturns($milestone_2_id)->getMock();

        $this->virtual_top_milestone = Mockery::spy(\Planning_VirtualTopMilestone::class)
            ->shouldReceive('getArtifactId')->andReturns(null)->getMock();

        $this->backlog_1       = Mockery::spy(\AgileDashboard_Milestone_Backlog_Backlog::class);
        $this->backlog_2       = Mockery::spy(\AgileDashboard_Milestone_Backlog_Backlog::class);
        $this->backlog_factory = Mockery::spy(\AgileDashboard_Milestone_Backlog_BacklogFactory::class);

        $this->backlog_item_collection_factory = Mockery::spy(
            \AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory::class
        );

        $this->sequence_id_manager = new AgileDashboard_SequenceIdManager(
            $this->backlog_factory,
            $this->backlog_item_collection_factory
        );
        $this->user                = new PFUser(['language_id' => 'en']);

        $this->artifact_id_1 = 123;
        $this->artifact_id_2 = 456;
        $this->artifact_id_3 = 789;

        $this->artifact_1 = Mockery::mock(Tracker_Artifact::class);
        $this->artifact_1->shouldReceive('getId')->andReturn($this->artifact_id_1);
        $this->artifact_2 = Mockery::mock(Tracker_Artifact::class);
        $this->artifact_2->shouldReceive('getId')->andReturn($this->artifact_id_2);
        $this->artifact_3 = Mockery::mock(Tracker_Artifact::class);
        $this->artifact_3->shouldReceive('getId')->andReturn($this->artifact_id_3);

        $artifact_id_4       = 254;
        $artifact_id_5       = 255;
        $this->artifact_id_6 = 256;

        $this->artifact_4 = Mockery::mock(Tracker_Artifact::class);
        $this->artifact_4->shouldReceive('getId')->andReturn($artifact_id_4);
        $this->artifact_5 = Mockery::mock(Tracker_Artifact::class);
        $this->artifact_5->shouldReceive('getId')->andReturn($artifact_id_5);
        $this->artifact_6 = Mockery::mock(Tracker_Artifact::class);
        $this->artifact_6->shouldReceive('getId')->andReturn($this->artifact_id_6);

        $this->backlog_item_1 = Mockery::spy(\AgileDashboard_Milestone_Backlog_BacklogItem::class)
            ->shouldReceive('getArtifact')->andReturns($this->artifact_1)->getMock();
        $this->backlog_item_2 = Mockery::spy(\AgileDashboard_Milestone_Backlog_BacklogItem::class)
            ->shouldReceive('getArtifact')->andReturns($this->artifact_2)->getMock();
        $this->backlog_item_3 = Mockery::spy(\AgileDashboard_Milestone_Backlog_BacklogItem::class)
            ->shouldReceive('getArtifact')->andReturns($this->artifact_3)->getMock();

        $this->items_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
    }

    public function testItReturnsNothingIfThereAreNoArtifactsInMilestonesBacklog(): void
    {
        $this->backlog_1->shouldReceive('getArtifacts')->with($this->user)
            ->andReturns(new AgileDashboard_Milestone_Backlog_DescendantItemsCollection())->once();

        $this->backlog_factory->shouldReceive('getBacklog')->with($this->milestone_1)
            ->andReturns($this->backlog_1)->once();

        $this->assertNull($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, 2));
    }

    public function testItReturnsNothingIfTheArtifactIsNotInTheMilestoneBacklog(): void
    {
        $backlog_items = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $backlog_items->push($this->artifact_1);
        $backlog_items->push($this->artifact_2);
        $backlog_items->push($this->artifact_3);

        $this->backlog_factory->shouldReceive('getBacklog')->with($this->milestone_1)
            ->andReturns($this->backlog_1)->once();
        $this->backlog_1->shouldReceive('getArtifacts')->with($this->user)
            ->andReturns($backlog_items)->once();

        $this->assertNull($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, 2));
    }

    public function testItReturns1IfTheArtifactIsInFirstPlace(): void
    {
        $backlog_items = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $backlog_items->push($this->artifact_1);
        $backlog_items->push($this->artifact_2);
        $backlog_items->push($this->artifact_3);

        $this->backlog_factory->shouldReceive('getBacklog')->with($this->milestone_1)
            ->andReturns($this->backlog_1)->once();
        $this->backlog_1->shouldReceive('getArtifacts')->with($this->user)
            ->andReturns($backlog_items)->once();

        $this->assertEquals(
            1,
            $this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $this->artifact_id_1)
        );
    }

    public function testItReturns2IfTheArtifactIsInFirstPlace(): void
    {
        $backlog_items = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $backlog_items->push($this->artifact_2);
        $backlog_items->push($this->artifact_1);
        $backlog_items->push($this->artifact_3);

        $this->backlog_factory->shouldReceive('getBacklog')->with($this->milestone_1)
            ->andReturns($this->backlog_1)->once();
        $this->backlog_1->shouldReceive('getArtifacts')->with($this->user)
            ->andReturns($backlog_items)->once();

        $this->assertEquals(
            2,
            $this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $this->artifact_id_1)
        );
    }

    public function testItKeepsInMemoryTheBacklogResult(): void
    {
        $backlog_items = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $backlog_items->push($this->artifact_2);
        $backlog_items->push($this->artifact_1);
        $backlog_items->push($this->artifact_3);

        $this->backlog_factory->shouldReceive('getBacklog')->with($this->milestone_1)
            ->andReturns($this->backlog_1)->once();
        $this->backlog_1->shouldReceive('getArtifacts')->with($this->user)
            ->andReturns($backlog_items)->once();

        $this->assertEquals(
            2,
            $this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $this->artifact_id_1)
        );
        $this->assertEquals(
            2,
            $this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $this->artifact_id_1)
        );
    }

    public function testItCanDealWithMultipleCallWithDifferentMilestones(): void
    {
        $backlog_items = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $backlog_items->push($this->artifact_2);
        $backlog_items->push($this->artifact_1);
        $backlog_items->push($this->artifact_3);

        $this->backlog_factory->shouldReceive('getBacklog')->with($this->milestone_1)
            ->andReturns($this->backlog_1)->once();
        $this->backlog_1->shouldReceive('getArtifacts')->with($this->user)
            ->andReturns($backlog_items)->once();

        $backlog_items = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $backlog_items->push($this->artifact_4);
        $backlog_items->push($this->artifact_5);
        $backlog_items->push($this->artifact_6);

        $this->backlog_factory->shouldReceive('getBacklog')->with($this->milestone_2)
            ->andReturns($this->backlog_2)->once();
        $this->backlog_2->shouldReceive('getArtifacts')->with($this->user)
            ->andReturns($backlog_items)->once();

        $this->assertEquals(
            2,
            $this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $this->artifact_id_1)
        );
        $this->assertEquals(
            3,
            $this->sequence_id_manager->getSequenceId($this->user, $this->milestone_2, $this->artifact_id_6)
        );
        $this->assertEquals(
            1,
            $this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $this->artifact_id_2)
        );
    }

    public function testItCanDealWithTopBacklog(): void
    {
        $this->virtual_top_milestone->shouldReceive('getArtifactId')->andReturn(2020);
        $this->items_collection->push($this->backlog_item_1);
        $this->items_collection->push($this->backlog_item_2);
        $this->items_collection->push($this->backlog_item_3);

        $this->backlog_item_collection_factory->shouldReceive('getUnassignedOpenCollection')
            ->andReturns($this->items_collection)->once();
        $this->backlog_factory->shouldReceive('getSelfBacklog')
            ->andReturns(Mockery::spy(AgileDashboard_Milestone_Backlog_Backlog::class))->once();

        $this->assertEquals(
            1,
            $this->sequence_id_manager->getSequenceId($this->user, $this->virtual_top_milestone, $this->artifact_id_1)
        );
        $this->assertEquals(
            3,
            $this->sequence_id_manager->getSequenceId($this->user, $this->virtual_top_milestone, $this->artifact_id_3)
        );
        $this->assertEquals(
            2,
            $this->sequence_id_manager->getSequenceId($this->user, $this->virtual_top_milestone, $this->artifact_id_2)
        );
    }
}
