<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

require_once dirname(__FILE__).'/../../../../common.php';

class Tracker_ArtifactFactory4BacklogItemFinderTest extends Tracker_ArtifactFactory {
    public function sortByPriority(array $artifacts) {
        return $artifacts;
    }
}

class AgileDashboard_Milestone_Backlog_BacklogItemFinderTest extends TuleapTestCase {

    private $story_tracker;
    private $epic_tracker;
    private $bug_tracker;
    private $artifact_factory;
    private $user;

    public function setUp() {
        parent::setUp();
        $this->story_tracker    = aTracker()->withId(111)->build();
        $this->epic_tracker     = aTracker()->withId(222)->build();
        $this->bug_tracker      = aTracker()->withId(333)->build();
        $this->artifact_factory = partial_mock('Tracker_ArtifactFactory4BacklogItemFinderTest', array('getChildrenForArtifacts'));
        $this->user             = mock('PFUser');
    }

    public function itReturnsAnEmptyArrayWhenNoData() {
        $finder = new AgileDashboard_Milestone_Backlog_ArtifactsFinder($this->artifact_factory, array(), array());
        $this->assertEqual($finder->getArtifacts($this->user), array());
    }

    public function itReturnsTheMilestoneBacklogAsIs() {
         $submilestone_trackers = array(
            $this->story_tracker
        );
        $milestone_backlog = array(
            anArtifact()->withId(1)->withTracker($this->story_tracker)->build(),
            anArtifact()->withId(2)->withTracker($this->story_tracker)->build(),
        );
        $finder = new AgileDashboard_Milestone_Backlog_ArtifactsFinder($this->artifact_factory, $milestone_backlog, $submilestone_trackers);
        $this->assertEqual($finder->getArtifacts($this->user), $milestone_backlog);
    }

    public function itFetchAllChildrenArtifactsAllAtOnce() {
        $submilestone_trackers = array(
            $this->story_tracker
        );

        $epic1 = anArtifact()->withId(1)->withTracker($this->epic_tracker)->build();
        $epic2 = anArtifact()->withId(2)->withTracker($this->epic_tracker)->build();
        $milestone_backlog = array(
            $epic1,
            $epic2,
        );

        expect($this->artifact_factory)->getChildrenForArtifacts($this->user, array($epic1, $epic2))->once()->returnsEmptyDar();

        $finder = new AgileDashboard_Milestone_Backlog_ArtifactsFinder($this->artifact_factory, $milestone_backlog, $submilestone_trackers);
        $finder->getArtifacts($this->user);
    }

    public function itReturnsChildrenOfParentMilestone() {
        $submilestone_trackers = array(
            $this->story_tracker
        );

        $epic1 = anArtifact()->withId(1)->withTracker($this->epic_tracker)->build();
        $epic2 = anArtifact()->withId(2)->withTracker($this->epic_tracker)->build();
        $milestone_backlog = array(
            $epic1,
            $epic2,
        );

        stub($this->artifact_factory)->getChildrenForArtifacts($this->user, array($epic1, $epic2))->returnsDar(
            anArtifact()->withId(55)->withTracker($this->story_tracker)->build(),
            anArtifact()->withId(56)->withTracker($this->story_tracker)->build()
        );

        $finder = new AgileDashboard_Milestone_Backlog_ArtifactsFinder($this->artifact_factory, $milestone_backlog, $submilestone_trackers);
        $artifacts = $finder->getArtifacts($this->user);
        $this->assertCount($artifacts, 2);

        $this->assertEqual($artifacts[0]->getId(), 55);
        $this->assertEqual($artifacts[1]->getId(), 56);
    }

    public function itReturnsParentMilestoneBacklogAndChildrenOfParentMilestone() {
        $submilestone_trackers = array(
            $this->story_tracker
        );

        $epic1 = anArtifact()->withId(1)->withTracker($this->epic_tracker)->build();

        $milestone_backlog = array(
            $epic1,
            anArtifact()->withId(2)->withTracker($this->story_tracker)->build(),
        );

        stub($this->artifact_factory)->getChildrenForArtifacts($this->user, array($epic1))->returnsDar(
            anArtifact()->withId(55)->withTracker($this->story_tracker)->build(),
            anArtifact()->withId(56)->withTracker($this->story_tracker)->build()
        );

        $finder = new AgileDashboard_Milestone_Backlog_ArtifactsFinder($this->artifact_factory, $milestone_backlog, $submilestone_trackers);
        $artifacts = $finder->getArtifacts($this->user);
        $this->assertCount($artifacts, 3);

        $this->assertEqual($artifacts[0]->getId(), 2);
        $this->assertEqual($artifacts[1]->getId(), 55);
        $this->assertEqual($artifacts[2]->getId(), 56);
    }

     public function itReturnsThreeLevelDeepArtifacts() {
        $submilestone_trackers = array(
            $this->story_tracker,
            $this->bug_tracker,
        );

        $epic1 = anArtifact()->withId(1)->withTracker($this->epic_tracker)->build();
        $epic56 = anArtifact()->withId(56)->withTracker($this->epic_tracker)->build();

        $milestone_backlog = array(
            $epic1,
            anArtifact()->withId(2)->withTracker($this->story_tracker)->build(),
        );

        stub($this->artifact_factory)->getChildrenForArtifacts($this->user, array($epic1))->returnsDar(
            anArtifact()->withId(55)->withTracker($this->story_tracker)->build(),
            $epic56
        );

        stub($this->artifact_factory)->getChildrenForArtifacts($this->user, array($epic56))->returnsDar(
            anArtifact()->withId(60)->withTracker($this->bug_tracker)->build(),
            anArtifact()->withId(61)->withTracker(aTracker()->withId(999)->build())->build(),
            anArtifact()->withId(62)->withTracker($this->story_tracker)->build()
        );

        $finder = new AgileDashboard_Milestone_Backlog_ArtifactsFinder($this->artifact_factory, $milestone_backlog, $submilestone_trackers);
        $artifacts = $finder->getArtifacts($this->user);
        $this->assertCount($artifacts, 4);

        $this->assertEqual($artifacts[0]->getId(), 2);
        $this->assertEqual($artifacts[1]->getId(), 55);
        $this->assertEqual($artifacts[2]->getId(), 60);
        $this->assertEqual($artifacts[3]->getId(), 62);
    }
}

?>
