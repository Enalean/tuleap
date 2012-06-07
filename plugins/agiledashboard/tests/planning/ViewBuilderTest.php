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

require_once dirname(__FILE__).'/../../include/Planning/ViewBuilder.class.php';
require_once dirname(__FILE__).'/../../../tracker/tests/builders/aCrossSearchCriteria.php';
require_once dirname(__FILE__).'/../builders/aPlanning.php';

class ViewBuilderTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        
        $this->backlog_tracker_id = 486;
        $this->descendant_ids     = array($this->backlog_tracker_id, 123, 456);
        
        $this->hierarchy_factory  = stub('Tracker_HierarchyFactory')->getDescendantIds()->returns($this->descendant_ids);
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
        $this->builder->setHierarchyFactory($this->hierarchy_factory);
        $this->planning = aPlanning()->build();
    }
    
    private function build() {
        return $this->builder->build($this->user, $this->project, $this->cross_search_criteria, array(), $this->backlog_tracker_id, $this->planning, '');
    }
    
    public function itRetrievesTheBacklogTrackerChildren() {
        $tracker_ids = $this->builder->getDescendantIds($this->backlog_tracker_id);
        $this->assertEqual($tracker_ids, $this->descendant_ids);
    }
    
    public function itRetrievesTheArtifactsFromTheBacklogTrackerAndItsChildrenTrackers() {
        stub($this->search)->getHierarchicallySortedArtifacts()->returns(new TreeNode());
        $this->search->expectOnce('getHierarchicallySortedArtifacts', array($this->user, $this->project, $this->descendant_ids, $this->cross_search_criteria, array()));
        $this->build();
    }
    
    public function itRemovesRootArtifactsThatDoNotMatchTheBacklogTracker() {
        $story = array('id' => 1, 'tracker_id' => $this->backlog_tracker_id);
        $task = array('id' => 2, 'tracker_id' => 123);
        $bug = array('id' => 3, 'tracker_id' => 999);

        $root = new TreeNode();
        $story_node = new TreeNode();
        $task_node = new TreeNode();
        $bug_node = new TreeNode();

        $story_node->setData($story);
        $task_node->setData($task);
        $bug_node->setData($bug);

        $story_node->addChild($task_node);
        $root->addChild($story_node);
        $root->addChild($bug_node);

        // Source:
        // .
        // |-- Story
        // |   `-- Task
        // `-- Bug

        $this->builder->filterNonPlannableNodes($this->backlog_tracker_id, $root);

        // Expectation:
        // .
        // `-- Story
        //     `-- Task


        $root_children = $root->getChildren();
        $this->assertEqual(count($root_children), 1);
        $this->assertEqual($root_children[0], $story_node);

        $story_children = $root_children[0]->getChildren();
        $this->assertEqual(count($story_children), 1);
        $this->assertEqual($story_children[0], $task_node);
    }

    //stub($this->search)->getHierarchicallySortedArtifacts()->returns($root);
    //$this->build();

    public function itBuildsPlanningContentView() {
        stub($this->search)->getHierarchicallySortedArtifacts()->returns(new TreeNode());
        $this->assertIsA($this->build(), 'Planning_SearchContentView');
    }
}

?>
