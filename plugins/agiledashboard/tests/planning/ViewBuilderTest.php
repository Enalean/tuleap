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
require_once dirname(__FILE__).'/../common.php';
require_once AGILEDASHBOARD_BASE_DIR.'/Planning/ViewBuilder.class.php';
require_once TRACKER_BASE_DIR.'/../tests/builders/aCrossSearchCriteria.php';
require_once dirname(__FILE__).'/../builders/aPlanning.php';

class ViewBuilderTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        
        $this->backlog_tracker_id  = 486;
        $this->backlog_tracker_ids = array($this->backlog_tracker_id, 123, 456);
        
        $this->backlog_actions_presenter = mock('Planning_BacklogActionsPresenter');
        
        $this->formElementFactory = mock('Tracker_FormElementFactory');
        $this->tracker_factory    = mock('TrackerFactory');
        $this->search             = mock('Tracker_CrossSearch_Search');
        $this->criteria_builder   = mock('Tracker_CrossSearch_CriteriaBuilder');
        
        
        $this->tracker_factory->setReturnValue('getTrackersByGroupIdUserCanView', $this->backlog_tracker_id);
        
        $this->criteria_builder->setReturnValue('getCriteria', array());
        
        $this->user    = aUser()->build();
        $this->project = mock('Project');
        
        $this->cross_search_criteria = aCrossSearchCriteria()->build();
        
        $this->builder  = new Planning_ViewBuilder($this->formElementFactory, $this->search, $this->criteria_builder);

        $this->planning = aPlanning()->build();
    }
    
    private function build() {
        return $this->builder->build(
            $this->user,
            $this->project,
            $this->cross_search_criteria,
            array(),
            $this->backlog_tracker_ids,
            $this->planning,
            $this->backlog_actions_presenter,
            ''
        );
    }
    
    public function itRetrievesTheArtifactsFromTheBacklogTrackerAndItsChildrenTrackers() {
        stub($this->search)->getHierarchicallySortedArtifacts()->returns(new TreeNode());
        $this->search->expectOnce('getHierarchicallySortedArtifacts', array($this->user, $this->project, $this->backlog_tracker_ids, $this->cross_search_criteria, array()));
        $this->build();
    }
    
    public function itBuildsPlanningContentView() {
        stub($this->search)->getHierarchicallySortedArtifacts()->returns(new TreeNode());
        $this->assertIsA($this->build(), 'Planning_SearchContentView');
    }
}

?>
