<?php
/**
 * Copyright Enalean (c) 2013 - 2018. All rights reserved.
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

require_once __DIR__ . '/../../../../bootstrap.php';

class AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactoryTest extends TuleapTestCase
{
    private $backlog;

    /** @var AgileDashboard_BacklogItemDao */
    private $dao;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory */
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

    public function setUp()
    {
        parent::setUp();

        $this->dao                              = mock('AgileDashboard_BacklogItemDao');
        $this->artifact_factory                 = mock('Tracker_ArtifactFactory');
        $this->form_element_factory             = mock('Tracker_FormElementFactory');
        $this->milestone_factory                = mock('Planning_MilestoneFactory');
        $this->planning_factory                 = mock('PlanningFactory');
        $this->backlog_item_builder             = new AgileDashboard_Milestone_Backlog_BacklogItemBuilder();
        $this->remaining_effort_value_retriever = mock('Tuleap\AgileDashboard\BacklogItem\RemainingEffortValueRetriever');

        $this->user = mock('PFUser');

        $planning        = mock('Planning');
        stub($planning)->getBacklogTrackers()->returns(array(mock('Tracker')));
        stub($planning)->getPlanningTracker()->returns(mock('Tracker'));

        $artifact        = aMockArtifact()->build();
        $this->milestone = aMilestone()->withArtifact($artifact)->withPlanning($planning)->build();

        $this->factory = partial_mock(
            'AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory',
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
                $this->planning_factory,
                $this->backlog_item_builder,
                $this->remaining_effort_value_retriever
            )
        );
        stub($this->factory)->userCanReadBacklogTitleField()->returns(true);
        stub($this->factory)->userCanReadBacklogStatusField()->returns(true);

        $this->tracker1 = mock('Tracker');
        $this->tracker2 = mock('Tracker');
        $this->tracker3 = mock('Tracker');

        $this->story1 = anArtifact()->withTitle('story 1')->withId($this->open_story_id)->withStatus(Tracker_Semantic_Status::OPEN)->withTracker($this->tracker1)->build();
        $this->story2 = anArtifact()->withTitle('story 2')->withId($this->open_unplanned_story_id)->withStatus(Tracker_Semantic_Status::OPEN)->withTracker($this->tracker2)->build();
        $this->story3 = anArtifact()->withTitle('story 3')->withId($this->closed_story_id)->withStatus(Tracker_Semantic_Status::CLOSED)->withTracker($this->tracker3)->build();

        $backlog_items = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $backlog_items->push($this->story1);
        $backlog_items->push($this->story2);
        $backlog_items->push($this->story3);

        $this->backlog = stub('AgileDashboard_Milestone_Backlog_Backlog')->getArtifacts($this->user)->returns($backlog_items);
        stub($this->backlog)->getMilestoneBacklogArtifactsTracker()->returns(mock('Tracker'));

        $this->redirect_to_self = 'whatever';

        stub($this->dao)->getArtifactsSemantics()->returnsDar(
            array(
                'id'                          => $this->open_story_id,
                Tracker_Semantic_Title::NAME  => 'Story is open',
                'title_format'                => 'text',
                Tracker_Semantic_Status::NAME => AgileDashboard_BacklogItemDao::STATUS_OPEN
            ),
            array(
                'id'                          => $this->open_unplanned_story_id,
                Tracker_Semantic_Title::NAME  => 'Story is open and unplanned',
                'title_format'                => 'text',
                Tracker_Semantic_Status::NAME => AgileDashboard_BacklogItemDao::STATUS_OPEN
            ),
            array(
                'id'                          => $this->closed_story_id,
                Tracker_Semantic_Title::NAME  => 'Story is closed',
                'title_format'                => 'text',
                Tracker_Semantic_Status::NAME => AgileDashboard_BacklogItemDao::STATUS_CLOSED
            )
        );

        $sub_milestone1 = stub('Planning_Milestone')->getArtifactId()->returns(121);
        $sub_milestone2 = stub('Planning_Milestone')->getArtifactId()->returns(436);
        stub($this->milestone_factory)->getSubMilestones()->returns(
            array($sub_milestone1, $sub_milestone2)
        );

        stub($this->artifact_factory)->getParents()->returns(array());
        stub($this->artifact_factory)->getTitleFromRowAsText()->returnsAt(0, 'Story is open');
        stub($this->artifact_factory)->getTitleFromRowAsText()->returnsAt(1, 'Story is open and unplanned');
        stub($this->artifact_factory)->getTitleFromRowAsText()->returnsAt(2, 'Story is closed');
    }

    public function itCreatesContentWithOneElementInTodo() {
        stub($this->dao)->getPlannedItemIds()->returns(array());
        stub($this->form_element_factory)->getUsedFieldByNameForUser()->returns(aMockField()->build());

        $content = $this->factory->getTodoCollection(
            $this->user,
            $this->milestone,
            $this->backlog,
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
            $this->backlog,
            $this->redirect_to_self
        );

        $row = $content->current();
        $this->assertEqual($row->id(), $this->closed_story_id);
    }

    public function itSetInitialEffortForOpenStories() {
        stub($this->dao)->getPlannedItemIds()->returns(array());

        $field = mock('Tracker_FormElement_Field_Float');

        stub($field)->getComputedValue()->returns(26);
        stub($field)->userCanRead()->returns(true);
        stub($this->factory)->getInitialEffortField()->returns($field);

        $content = $this->factory->getTodoCollection(
            $this->user,
            $this->milestone,
            $this->backlog,
            $this->redirect_to_self
        );

        $row = $content->current();
        $this->assertEqual($row->points(), 26);
    }

    public function itNotSetInitialEffortForOpenStoriesIfUserCannotRead() {
        stub($this->dao)->getPlannedItemIds()->returns(array());

        $field = mock('Tracker_FormElement_Field_Float');

        stub($field)->getComputedValue()->returns(26);
        stub($field)->userCanRead()->returns(false);
        stub($this->factory)->getInitialEffortField()->returns($field);

        $content = $this->factory->getTodoCollection(
            $this->user,
            $this->milestone,
            $this->backlog,
            $this->redirect_to_self
        );

        $row = $content->current();
        $this->assertEqual($row->points(), null);
    }

    public function itCreatesACollectionForOpenAndUnassignedElements() {
        $factory = partial_mock(
            'AgileDashboard_Milestone_Backlog_BacklogItemCollectionFactory',
            array('getUnplannedOpenCollection',),
            array(
                $this->dao,
                $this->artifact_factory,
                $this->form_element_factory,
                $this->milestone_factory,
                $this->planning_factory,
                $this->backlog_item_builder,
                $this->remaining_effort_value_retriever
            )
        );

        $redirect_to_self = 'tra la la';
        $backlog_item1 = new AgileDashboard_BacklogItemPresenter($this->story1, $redirect_to_self, false);
        $backlog_item2 = new AgileDashboard_BacklogItemPresenter($this->story2, $redirect_to_self, false);
        $backlog_item3 = new AgileDashboard_BacklogItemPresenter($this->story3, $redirect_to_self, false);

        $mixed_collection = new AgileDashboard_Milestone_Backlog_BacklogItemPresenterCollection();
        $mixed_collection->push($backlog_item1);
        $mixed_collection->push($backlog_item2);
        $mixed_collection->push($backlog_item3);

        stub($factory)->getUnplannedOpenCollection()->returns($mixed_collection);

        stub($this->tracker1)->getProject()->returns(mock('Project'));

        $planning1 = mock('Planning');
        $planning2 = mock('Planning');
        stub($this->planning_factory)->getPlannings()->returns(array($planning1, $planning2));

        stub($this->artifact_factory)->getArtifactIdsLinkedToTrackers()->returns(array(12 => true));

        $cleaned_collection = $factory->getUnassignedOpenCollection($this->user, $this->milestone, $this->backlog, $this->redirect_to_self);
        $this->assertEqual($cleaned_collection->count(), 2);
    }
}
