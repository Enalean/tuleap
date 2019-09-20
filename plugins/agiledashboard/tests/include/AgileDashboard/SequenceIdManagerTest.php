<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

require_once dirname(__FILE__).'/../../bootstrap.php';

class AgileDashboard_SequenceIdManagerTest extends TuleapTestCase
{

    private $artifact_id_1;
    private $artifact_id_2;
    private $artifact_id_3;

    private $artifact_id_4;
    private $artifact_id_5;
    private $artifact_id_6;

    private $artifact_1;
    private $artifact_2;
    private $artifact_3;

    private $artifact_4;
    private $artifact_5;
    private $artifact_6;

    /**
     * @var AgileDashboard_SequenceIdManager
     */
    private $sequence_id_manager;

    private $milestone_1;
    private $milestone_2;
    private $milestone_1_id;
    private $milestone_2_id;

    private $backlog_factory;
    private $backlog_1;
    private $backlog_2;

    private $user;

    private $backlog_item_collection_factory;

    private $backlog_item_1;
    private $backlog_item_2;
    private $backlog_item_3;

    private $items_collection;

    public function setUp()
    {
        parent::setUp();

        $this->milestone_1_id = 132;
        $this->milestone_1    = stub('Planning_ArtifactMilestone')->getArtifactId()->returns($this->milestone_1_id);

        $this->milestone_2_id = 853;
        $this->milestone_2    = stub('Planning_ArtifactMilestone')->getArtifactId()->returns($this->milestone_2_id);

        $this->virtual_top_milestone = stub('Planning_VirtualTopMilestone')->getArtifactId()->returns(null);

        $this->backlog_1       = mock('AgileDashboard_Milestone_Backlog_Backlog');
        $this->backlog_2       = mock('AgileDashboard_Milestone_Backlog_Backlog');
        $this->backlog_factory = mock('AgileDashboard_Milestone_Backlog_BacklogFactory');
        stub($this->backlog_factory)->getBacklog($this->milestone_1)->returns($this->backlog_1);
        stub($this->backlog_factory)->getBacklog($this->milestone_2)->returns($this->backlog_2);

        $this->backlog_item_collection_factory = mock('AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory');

        $this->sequence_id_manager = new AgileDashboard_SequenceIdManager($this->backlog_factory, $this->backlog_item_collection_factory);
        $this->user                = aUser()->build();

        $this->artifact_id_1  = 123;
        $this->artifact_id_2  = 456;
        $this->artifact_id_3  = 789;

        $this->artifact_1 = anArtifact()->withId($this->artifact_id_1)->build();
        $this->artifact_2 = anArtifact()->withId($this->artifact_id_2)->build();
        $this->artifact_3 = anArtifact()->withId($this->artifact_id_3)->build();

        $this->artifact_id_4 = 254;
        $this->artifact_id_5 = 255;
        $this->artifact_id_6 = 256;

        $this->artifact_4 = anArtifact()->withId($this->artifact_id_4)->build();
        $this->artifact_5 = anArtifact()->withId($this->artifact_id_5)->build();
        $this->artifact_6 = anArtifact()->withId($this->artifact_id_6)->build();

        $this->backlog_item_1 = stub('AgileDashboard_Milestone_Backlog_BacklogItem')->getArtifact()->returns($this->artifact_1);
        $this->backlog_item_2 = stub('AgileDashboard_Milestone_Backlog_BacklogItem')->getArtifact()->returns($this->artifact_2);
        $this->backlog_item_3 = stub('AgileDashboard_Milestone_Backlog_BacklogItem')->getArtifact()->returns($this->artifact_3);

        $this->items_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
    }

    public function itReturnsNothingIfThereAreNoArtifactsInMilestonesBacklog()
    {
        $artifact_id = 2;

        stub($this->backlog_1)->getArtifacts($this->user)->returns(new AgileDashboard_Milestone_Backlog_DescendantItemsCollection());

        expect($this->backlog_factory)->getBacklog($this->milestone_1)->once();

        $this->assertNull($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $artifact_id));
    }

    public function itReturnsNothingIfTheArtifactIsNotInTheMilestoneBacklog()
    {
        $artifact_id = 2;

        $backlog_items = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $backlog_items->push($this->artifact_1);
        $backlog_items->push($this->artifact_2);
        $backlog_items->push($this->artifact_3);

        stub($this->backlog_1)->getArtifacts($this->user)->returns(
            $backlog_items
        );

        expect($this->backlog_1)->getArtifacts($this->user)->once();

        $this->assertNull($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $artifact_id));
    }

    public function itReturns1IfTheArtifactIsInFirstPlace()
    {
        $artifact_id = $this->artifact_id_1;

        $backlog_items = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $backlog_items->push($this->artifact_1);
        $backlog_items->push($this->artifact_2);
        $backlog_items->push($this->artifact_3);

        stub($this->backlog_1)->getArtifacts($this->user)->returns(
            $backlog_items
        );

        expect($this->backlog_1)->getArtifacts($this->user)->once();

        $this->assertEqual($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $artifact_id), 1);
    }

    public function itReturns2IfTheArtifactIsInFirstPlace()
    {
        $artifact_id = $this->artifact_id_1;

        $backlog_items = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $backlog_items->push($this->artifact_2);
        $backlog_items->push($this->artifact_1);
        $backlog_items->push($this->artifact_3);

        stub($this->backlog_1)->getArtifacts($this->user)->returns(
            $backlog_items
        );

        expect($this->backlog_1)->getArtifacts($this->user)->once();

        $this->assertEqual($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $artifact_id), 2);
    }

    public function itKeepsInMemoryTheBacklogResult()
    {
        $artifact_id = $this->artifact_id_1;

        $backlog_items = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $backlog_items->push($this->artifact_2);
        $backlog_items->push($this->artifact_1);
        $backlog_items->push($this->artifact_3);

        stub($this->backlog_1)->getArtifacts($this->user)->returns(
            $backlog_items
        );

        expect($this->backlog_1)->getArtifacts($this->user)->once();

        $this->assertEqual($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $artifact_id), 2);
        $this->assertEqual($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $artifact_id), 2);
    }

    public function itCanDealWithMultipleCallWithDifferentMilestones()
    {
        $backlog_items = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $backlog_items->push($this->artifact_2);
        $backlog_items->push($this->artifact_1);
        $backlog_items->push($this->artifact_3);

        stub($this->backlog_1)->getArtifacts($this->user)->returns(
            $backlog_items
        );

        $backlog_items = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $backlog_items->push($this->artifact_4);
        $backlog_items->push($this->artifact_5);
        $backlog_items->push($this->artifact_6);

        stub($this->backlog_2)->getArtifacts($this->user)->returns(
            $backlog_items
        );

        stub($this->backlog_2)->getArtifacts($this->user)->returns(
            $backlog_items
        );

        expect($this->backlog_factory)->getBacklog()->count(2);

        $this->assertEqual($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $this->artifact_id_1), 2);
        $this->assertEqual($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_2, $this->artifact_id_6), 3);
        $this->assertEqual($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $this->artifact_id_2), 1);
    }

    public function itCanDealWithTopBacklog()
    {

        $this->items_collection->push($this->backlog_item_1);
        $this->items_collection->push($this->backlog_item_2);
        $this->items_collection->push($this->backlog_item_3);

        stub($this->backlog_item_collection_factory)->getUnassignedOpenCollection()->returns($this->items_collection);
        stub($this->backlog_factory)->getSelfBacklog()->returns(\Mockery::spy(AgileDashboard_Milestone_Backlog_Backlog::class));

        expect($this->backlog_factory)->getSelfBacklog()->once();
        expect($this->backlog_item_collection_factory)->getUnassignedOpenCollection()->once();

        $this->assertEqual($this->sequence_id_manager->getSequenceId($this->user, $this->virtual_top_milestone, $this->artifact_id_1), 1);
        $this->assertEqual($this->sequence_id_manager->getSequenceId($this->user, $this->virtual_top_milestone, $this->artifact_id_3), 3);
        $this->assertEqual($this->sequence_id_manager->getSequenceId($this->user, $this->virtual_top_milestone, $this->artifact_id_2), 2);
    }
}
