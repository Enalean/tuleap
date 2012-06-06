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

require_once dirname(__FILE__) .'/../../include/Planning/ArtifactTreeNodeVisitor.class.php';
require_once dirname(__FILE__) .'/../../../tracker/include/constants.php';
require_once TRACKER_BASE_DIR .'/Tracker/Tracker.class.php';
require_once TRACKER_BASE_DIR .'/../tests/builders/aTracker.php';
require_once TRACKER_BASE_DIR .'/../tests/builders/anArtifact.php';
require_once dirname(__FILE__).'/../builders/aPlanning.php';

class Planning_ArtifactTreeNodeVisitorTest extends TuleapTestCase {
    public function itWrapsAnArtifactInATreeNode() {
        $tracker           = mock('Tracker');
        $children_trackers = array(mock('Tracker'), mock('Tracker'));
        
        $hierarchy_factory = mock('Tracker_Hierarchy_HierarchicalTrackerFactory');
        stub($hierarchy_factory)->getChildren($tracker)->returns($children_trackers);
        
        $artifact = mock('Tracker_Artifact');
        stub($artifact)->getId()->returns(123);
        stub($artifact)->getTitle()->returns('Foo');
        stub($artifact)->getUri()->returns('/bar');
        stub($artifact)->getXRef()->returns('art #123');
        stub($artifact)->getTracker()->returns($tracker);
        
        $artifact_factory = mock('Tracker_ArtifactFactory');
        stub($artifact_factory)->getArtifactById(123)->returns($artifact);
        
        $planning = mock('Planning');
        
        $node    = new TreeNode(array('id' => 123));
        $visitor = new Planning_ArtifactTreeNodeVisitor($planning, $artifact_factory, $hierarchy_factory, 'baz');
        
        $visitor->visit($node);
        
        $data = $node->getData();
        $this->assertEqual($data['id'],                   123);
        $this->assertEqual($data['title'],                'Foo');
        $this->assertEqual($data['uri'],                  '/bar');
        $this->assertEqual($data['xref'],                 'art #123');
        $this->assertEqual($data['class'],                'baz');
        $this->assertEqual($data['allowedChildrenTypes'], $children_trackers);
    }
}

class Planning_ArtifactTreeNodeVisitor_PlanningDraggableTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $artifact_id               = 123;
        $this->planning_tracker_id = 456;
        $this->other_tracker_id    = 789;
        
        $planning       = stub('Planning')->getBacklogTrackerId()->returns($this->planning_tracker_id);
        $this->artifact = mock('Tracker_Artifact');
        
        $artifact_factory  = mock('Tracker_ArtifactFactory');
        $hierarchy_factory = mock('Tracker_Hierarchy_HierarchicalTrackerFactory');
        
        stub($artifact_factory)->getArtifactById($artifact_id)->returns($this->artifact);
        
        $this->node    = new TreeNode(array('id' => $artifact_id));
        $this->visitor = new Planning_ArtifactTreeNodeVisitor($planning, $artifact_factory, $hierarchy_factory, 'whatever');
    }
    
    public function itKnowsDraggablePlanningItems() {
        stub($this->artifact)->getTrackerId()->returns($this->planning_tracker_id);
        
        $this->visitor->visit($this->node);
        
        $data = $this->node->getData();
        $this->assertEqual($data['planningDraggable'], true);
    }
    
    public function itKnowsNonDraggablePlanningItems() {
        stub($this->artifact)->getTrackerId()->returns($this->other_tracker_id);
        
        $this->visitor->visit($this->node);
        
        $data = $this->node->getData();
        $this->assertEqual($data['planningDraggable'], false);
    }
}

?>
