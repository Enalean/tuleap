<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__).'/../../include/Planning/Milestone.class.php';
require_once dirname(__FILE__).'/../builders/aPlanning.php';
require_once dirname(__FILE__).'/../../../tracker/tests/builders/anArtifact.php';

class MilestoneTest extends TuleapTestCase {
    
    private $group_id;
    private $planning;
    private $artifact;
    
    /**
     * @var Planning_Milestone
     */
    private $milestone;
    
    public function setUp() {
        parent::setUp();
        
        $this->group_id  = 123;
        $this->planning  = aPlanning()->build();
        $this->artifact  = anArtifact()->build();
        $this->milestone = new Planning_Milestone($this->group_id,
                                                  $this->planning,
                                                  $this->artifact);
    }
    
    public function itRepresentsAnArtifact() {
        $this->assertEqual($this->milestone->getArtifact(), $this->artifact);
    }
    
    public function itDelegatesArtifactIdRetrieval() {
        $this->assertEqual($this->milestone->getArtifactId(), $this->artifact->getId());
    }
    
    public function itMayHavePlannedArtifacts() {
        $this->assertEqual($this->milestone->getPlannedArtifacts(), null);
        
        $planned_artifacts = new TreeNode();
        $this->milestone   = new Planning_Milestone($this->group_id,
                                                     $this->planning,
                                                     $this->artifact,
                                                     $planned_artifacts);
        
        $this->assertEqual($this->milestone->getPlannedArtifacts(), $planned_artifacts);
    }
    
    public function itMayBeNull() {
        $this->milestone = new Planning_NoMilestone($this->group_id, $this->planning);
        
        $this->assertNull($this->milestone->getArtifact());
        $this->assertNull($this->milestone->getArtifactId());
    }
    
    public function itHasNoSubMilestonesInitially() {
        $this->milestone = new Planning_Milestone($this->group_id,
                                                  $this->planning,
                                                  $this->artifact);
        $this->assertIdentical($this->milestone->getSubMilestones(), array());
    }
}

require_once dirname(__FILE__).'/../builders/aMilestone.php';

class Planning_Milestone_WhenFirstCreatedTest extends TuleapTestCase {
    
    public function setUp() {
        $this->group_id  = 123;
        $this->planning  = mock('Planning');
        $this->artifact  = mock('Tracker_Artifact');
        $this->milestone = new Planning_Milestone($this->group_id,
                                                  $this->planning,
                                                  $this->artifact);
    }
    
    public function itHasNoSubMilestones() {
        $this->assertIdentical($this->milestone->getSubMilestones(), array());
    }
    
    public function itAcceptsNewSubMilestones() {
        $sub_milestone_1 = aMilestone()->withinTheSameProjectAs($this->milestone)->build();
        $sub_milestone_2 = aMilestone()->withinTheSameProjectAs($this->milestone)->build();
        
        $this->milestone->addSubMilestones(array($sub_milestone_1, $sub_milestone_2));
        $this->assertIdentical($this->milestone->getSubMilestones(),
                               array($sub_milestone_1, $sub_milestone_2));
    }
}

class Planning_Milestone_WithSubMilestones extends TuleapTestCase {
    public function itCanBeAddedNewSubMilestones() {
        $sub_milestone_1 = aMilestone()->build();
        $sub_milestone_2 = aMilestone()->withinTheSameProjectAs($sub_milestone_1)->build();
        $sub_milestone_3 = aMilestone()->withinTheSameProjectAs($sub_milestone_1)->build();
        $sub_milestone_4 = aMilestone()->withinTheSameProjectAs($sub_milestone_1)->build();
        $this->milestone = aMilestone()->withinTheSameProjectAs($sub_milestone_1)
                                       ->withSubMilestones(array($sub_milestone_1, $sub_milestone_2))->build();
        
        $this->milestone->addSubMilestones(array($sub_milestone_3, $sub_milestone_4));
        
        $this->assertIdentical($this->milestone->getSubMilestones(),
                               array($sub_milestone_1, $sub_milestone_2, $sub_milestone_3, $sub_milestone_4));
    }
}
?>
