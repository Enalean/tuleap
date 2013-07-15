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

class AgileDashboard_Milestone_Backlog_BacklogRowCollectionFactoryTest extends TuleapTestCase {

    /** @var AgileDashboard_BacklogItemDao */
    private $dao;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogRowCollectionFactory */
    private $factory;

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    /** @var PFUser */
    private $user;

    /** @var Planning_ArtifactMilestone */
    private $milestone;

    private $open_story_id           = 12;
    private $open_unplanned_story_id = 47;
    private $closed_story_id         = 66;

    public function setUp() {
        parent::setUp();

        $this->dao                  = mock('AgileDashboard_BacklogItemDao');
        $this->artifact_factory     = mock('Tracker_ArtifactFactory');
        $this->form_element_factory = mock('Tracker_FormElementFactory');
        $this->milestone_factory    = mock('Planning_MilestoneFactory');

        $this->user = mock('PFUser');

        $planning        = mock('Planning');
        stub($planning)->getBacklogTracker()->returns(mock('Tracker'));
        stub($planning)->getPlanningTracker()->returns(mock('Tracker'));

        $artifact        = aMockArtifact()->build();
        $this->milestone = aMilestone()->withArtifact($artifact)->withPlanning($planning)->build();

        $this->factory = partial_mock(
            'AgileDashboard_Milestone_Backlog_BacklogRowCollectionFactory',
            array(
                'userCanReadBacklogTitleField',
                'userCanReadBacklogStatusField',
                'getInitialEffortField',
            ),
            array(
                $this->dao,
                $this->artifact_factory,
                $this->form_element_factory,
                $this->milestone_factory,
            )
        );
        stub($this->factory)->userCanReadBacklogTitleField()->returns(true);
        stub($this->factory)->userCanReadBacklogStatusField()->returns(true);

        $tracker1 = mock('Tracker');
        $tracker2 = mock('Tracker');
        $tracker3 = mock('Tracker');

        $story1 = anArtifact()->withId($this->open_story_id)->withTracker($tracker1)->build();
        $story2 = anArtifact()->withId($this->open_unplanned_story_id)->withTracker($tracker2)->build();
        $story3 = anArtifact()->withId($this->closed_story_id)->withTracker($tracker3)->build();
  
        $this->backlog_strategy = stub('AgileDashboard_Milestone_Backlog_BacklogStrategy')->getArtifacts($this->user)->returns(array($story1, $story2, $story3));
        $this->redirect_to_self = 'whatever';


        stub($this->dao)->getArtifactsSemantics()->returnsDar(
            array(
                'id'                          => $this->open_story_id,
                Tracker_Semantic_Title::NAME  => 'Story is open',
                Tracker_Semantic_Status::NAME => AgileDashboard_BacklogItemDao::STATUS_OPEN
            ),
            array(
                'id'                          => $this->open_unplanned_story_id,
                Tracker_Semantic_Title::NAME  => 'Story is open and unplanned',
                Tracker_Semantic_Status::NAME => AgileDashboard_BacklogItemDao::STATUS_OPEN
            ),
            array(
                'id'                          => $this->closed_story_id,
                Tracker_Semantic_Title::NAME  => 'Story is closed',
                Tracker_Semantic_Status::NAME => AgileDashboard_BacklogItemDao::STATUS_CLOSED
            )
        );

        $sub_milestone1 = stub('Planning_Milestone')->getArtifactId()->returns(121);
        $sub_milestone2 = stub('Planning_Milestone')->getArtifactId()->returns(436);
        stub($this->milestone_factory)->getSubMilestones()->returns(
            array($sub_milestone1, $sub_milestone2)
        );

        stub($this->artifact_factory)->getParents()->returns(array());
    }

    public function itCreatesContentWithOneElementInTodo() {
        stub($this->dao)->getPlannedItemIds()->returns(array());

        stub($this->form_element_factory)->getUsedFieldByNameForUser()->returns(aMockField()->build());

        $content = $this->factory->getTodoCollection(
            $this->user,
            $this->milestone,
            $this->backlog_strategy,
            $this->redirect_to_self
        );

        $row = $content->current();
        $this->assertEqual($row->id(), $this->open_story_id);
    }

    public function itCreatesContentWithOneElementInDone() {
        stub($this->dao)->getPlannedItemIds()->returns(array());

        stub($this->form_element_factory)->getUsedFieldByNameForUser()->returns(aMockField()->build());

        $content = $this->factory->getDoneCollection(
            $this->user,
            $this->milestone,
            $this->backlog_strategy,
            $this->redirect_to_self
        );

        $row = $content->current();
        $this->assertEqual($row->id(), $this->closed_story_id);
    }

    public function itSetEffortForOpenStories() {
        stub($this->dao)->getPlannedItemIds()->returns(array());

        $field = mock('Tracker_FormElement_Field_Float');

        stub($field)->getComputedValue()->returns(26);
        stub($this->factory)->getInitialEffortField()->returns($field);

        $content = $this->factory->getTodoCollection(
            $this->user,
            $this->milestone,
            $this->backlog_strategy,
            $this->redirect_to_self
        );

        $row = $content->current();
        $this->assertEqual($row->points(), 26);
    }

    public function itCreatesACollectionForOpenAndUnplannedElements() {
        stub($this->dao)->getPlannedItemIds(array(121,436))->returns(
            array($this->open_story_id)
        );

        $collection = $this->factory->getUnplannedOpenCollection(
            $this->user,
            $this->milestone,
            $this->backlog_strategy,
            $this->redirect_to_self
        );

        $row = $collection->current();
        $this->assertEqual($row->id(), $this->open_unplanned_story_id);
    }
}

?>
