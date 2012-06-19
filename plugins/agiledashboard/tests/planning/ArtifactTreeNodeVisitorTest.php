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
require_once TRACKER_BASE_DIR .'/Tracker/Hierarchy/HierarchyFactory.class.php';
require_once TRACKER_BASE_DIR .'/../tests/builders/aTracker.php';
require_once TRACKER_BASE_DIR .'/../tests/builders/anArtifact.php';
require_once dirname(__FILE__).'/../builders/aPlanning.php';

class Planning_ArtifactTreeNodeVisitorTest extends TuleapTestCase {

    public function itWrapsAnArtifactInATreeNode() {
        $tracker           = aMockTracker()->withId(23452345)->build();
        $children_trackers = array(mock('Tracker'), mock('Tracker'));
        
        $artifact = mock('Tracker_Artifact');
        stub($artifact)->getId()->returns(123);
        stub($artifact)->getTitle()->returns('Foo');
        stub($artifact)->getUri()->returns('/bar');
        stub($artifact)->getXRef()->returns('art #123');
        stub($artifact)->getTracker()->returns($tracker);
        stub($artifact)->getAllowedChildrenTypes()->returns($children_trackers);
        
        
        $planning = mock('Planning');
        
        $node    = new TreeNode(array('id' => 123));
        $node->setObject($artifact);
        
        $visitor = new Planning_ArtifactTreeNodeVisitor($planning, 'baz');
        
        $visitor->visit($node);
        
        $presenter = $node->getObject();
        $this->assertEqual(123, $presenter->getId());
        $this->assertEqual('Foo', $presenter->getTitle());
        $this->assertEqual('/bar', $presenter->getUrl());
        $this->assertEqual('art #123', $presenter->getXRef());
        $this->assertEqual('baz', $presenter->getCssClasses());
        $this->assertEqual($children_trackers, $presenter->allowedChildrenTypes());
    }
}

class Planning_ArtifactTreeNodeVisitor_PlanningDraggableTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $planning_tracker_id = 456;
        $other_tracker_id    = 789;
        
        $this->planning_tracker = aMockTracker()->withId($planning_tracker_id)->build();
        $this->other_tracker    = aMockTracker()->withId($other_tracker_id)->build();
        
        $planning       = stub('Planning')->getBacklogTrackerId()->returns($planning_tracker_id);
        $this->artifact = mock('Tracker_Artifact');
        
        
        $this->node    = new TreeNode();
        $this->node->setObject($this->artifact);
        $this->visitor = new Planning_ArtifactTreeNodeVisitor($planning, 'whatever');
    }
    
    public function itKnowsDraggablePlanningItems() {
        stub($this->artifact)->getTracker()->returns($this->planning_tracker);
        
        $this->visitor->visit($this->node);
        
        $this->assertEqual('whatever planning-draggable', $this->node->getObject()->getCssClasses());
    }
    
    public function itKnowsNonDraggablePlanningItems() {
        stub($this->artifact)->getTracker()->returns($this->other_tracker);
        
        $this->visitor->visit($this->node);
        
        $this->assertEqual('whatever', $this->node->getObject()->getCssClasses());
    }
}

?>
