<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class AgileDashboard_SequenceIdManagerTest extends TuleapTestCase {

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

    private $sequence_id_manager;

    private $milestone_1;
    private $milestone_2;
    private $milestone_1_id;
    private $milestone_2_id;

    private $strategy_factory;
    private $strategy_1;
    private $strategy_2;

    private $user;

    public function setUp() {
        parent::setUp();

        $this->milestone_1_id = 132;
        $this->milestone_1    = stub('Planning_ArtifactMilestone')->getArtifactId()->returns($this->milestone_1_id);

        $this->milestone_2_id = 853;
        $this->milestone_2    = stub('Planning_ArtifactMilestone')->getArtifactId()->returns($this->milestone_2_id);

        $this->strategy_1       = mock('AgileDashboard_Milestone_Backlog_DescendantBacklogStrategy');
        $this->strategy_2       = mock('AgileDashboard_Milestone_Backlog_DescendantBacklogStrategy');
        $this->strategy_factory = mock('AgileDashboard_Milestone_Backlog_BacklogStrategyFactory');
        stub($this->strategy_factory)->getBacklogStrategy($this->milestone_1)->returns($this->strategy_1);
        stub($this->strategy_factory)->getBacklogStrategy($this->milestone_2)->returns($this->strategy_2);

        $this->sequence_id_manager = new AgileDashboard_SequenceIdManager($this->strategy_factory);
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
    }

    public function itReturnsNothingIfThereAreNoArtifactsInMilestonesBacklog() {
        $artifact_id = 2;

        stub($this->strategy_1)->getArtifacts($this->user)->returns(array());

        expect($this->strategy_factory)->getBacklogStrategy($this->milestone_1)->once();

        $this->assertNull($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $artifact_id));
    }

    public function itReturnsNothingIfTheArtifactIsNotInTheMilestoneBacklog() {
        $artifact_id = 2;

        stub($this->strategy_1)->getArtifacts($this->user)->returns(
            array(
                $this->artifact_1,
                $this->artifact_2,
                $this->artifact_3
                )
        );

        expect($this->strategy_1)->getArtifacts($this->user)->once();

        $this->assertNull($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $artifact_id));
    }

    public function itReturns1IfTheArtifactIsInFirstPlace() {
        $artifact_id = $this->artifact_id_1;

        stub($this->strategy_1)->getArtifacts($this->user)->returns(
            array(
                $this->artifact_1,
                $this->artifact_2,
                $this->artifact_3
            )
        );

        expect($this->strategy_1)->getArtifacts($this->user)->once();

        $this->assertEqual($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $artifact_id), 1);
    }

    public function itReturns2IfTheArtifactIsInFirstPlace() {
        $artifact_id = $this->artifact_id_1;

        stub($this->strategy_1)->getArtifacts($this->user)->returns(
            array(
                $this->artifact_2,
                $this->artifact_1,
                $this->artifact_3
            )
        );

        expect($this->strategy_1)->getArtifacts($this->user)->once();

        $this->assertEqual($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $artifact_id), 2);
    }

    public function itKeepsInMemoryTheBacklogResult() {
        $artifact_id = $this->artifact_id_1;

        stub($this->strategy_1)->getArtifacts($this->user)->returns(
            array(
                $this->artifact_2,
                $this->artifact_1,
                $this->artifact_3
            )
        );

        expect($this->strategy_1)->getArtifacts($this->user)->once();

        $this->assertEqual($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $artifact_id), 2);
        $this->assertEqual($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $artifact_id), 2);
    }

    public function itCanDealWithMultipleCallWithDifferentMilestones() {
        stub($this->strategy_1)->getArtifacts($this->user)->returns(
            array(
                $this->artifact_2,
                $this->artifact_1,
                $this->artifact_3
            )
        );

        stub($this->strategy_2)->getArtifacts($this->user)->returns(
            array(
                $this->artifact_4,
                $this->artifact_5,
                $this->artifact_6
            )
        );

        expect($this->strategy_factory)->getBacklogStrategy()->count(2);

        $this->assertEqual($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $this->artifact_id_1), 2);
        $this->assertEqual($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_2, $this->artifact_id_6), 3);
        $this->assertEqual($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $this->artifact_id_2), 1);
    }
}
