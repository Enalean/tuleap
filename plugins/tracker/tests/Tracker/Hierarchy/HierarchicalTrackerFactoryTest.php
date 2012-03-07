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

require_once(dirname(__FILE__).'/../../../include/Tracker/Hierarchy/Dao.class.php');
require_once(dirname(__FILE__).'/../../../include/Tracker/Hierarchy/HierarchicalTrackerFactory.class.php');
require_once(dirname(__FILE__).'/../../../include/Tracker/TrackerFactory.class.php');
require_once(dirname(__FILE__).'/../../Test_Tracker_Builder.php');

Mock::generate('Tracker_Hierarchy_Dao');
Mock::generate('Project');
Mock::generate('TrackerFactory');

class HierarchicalTrackerFactoryTest extends UnitTestCase {
    
    function testGetWithChildren() {
        $tracker = aTracker()->withId(1)->build();
        
        $dao = new MockTracker_Hierarchy_Dao();
        $children_ids = TestHelper::arrayToDar(array('parent_id' => 1, 'child_id' => 2), 
                                               array('parent_id' => 1, 'child_id' => 3));
        $dao->setReturnValue('getChildren', $children_ids, array($tracker->getId()));
        
        
        $child1 = aTracker()->withId(2)->build();
        $child2 = aTracker()->withId(3)->build();
        $tracker_factory = new MockTrackerFactory();
        $tracker_factory->setReturnValue('getTrackerById', $child1, array(2));
        $tracker_factory->setReturnValue('getTrackerById', $child2, array(3));
        
        $factory = new Tracker_Hierarchy_HierarchicalTrackerFactory($tracker_factory, $dao);
        $hierarchical_tracker = $factory->getWithChildren($tracker);
        
        $children = $hierarchical_tracker->getChildren();
        $children = $this->assertChildEquals($children, $child1);
        $children = $this->assertChildEquals($children, $child2);
        $this->assertEqual(count($children), 0);
    }
    
    private function assertChildEquals($children, $tracker) {
        $child = array_shift($children);
        $this->assertEqual($child, $tracker);
        return $children;
    }
    
    public function testGetPossibleChildren() {
        $dao = new MockTracker_Hierarchy_Dao();
        
        $project_id = 100;
        $project    = new MockProject();
        $project->setReturnValue('getId', $project_id);
        $tracker    = aTracker()->withId(1)->withProject($project)->build();
        $hierarchical_tracker = new Tracker_Hierarchy_HierarchicalTracker($tracker, array());
        
        $possible_child_1 = aTracker()->withId(2)->build();
        $possible_child_2 = aTracker()->withId(3)->build();
        
        $project_trackers = array(
            1 => $tracker,
            2 => $possible_child_1,
            3 => $possible_child_2
        );
        
        $tracker_factory = new MockTrackerFactory();
        $tracker_factory->setReturnValue('getTrackersByGroupId', $project_trackers, array($project_id));
        
        $factory = new Tracker_Hierarchy_HierarchicalTrackerFactory($tracker_factory, $dao);
        
        $expected_possible_children = array($possible_child_1, $possible_child_2);
        $actual_possible_children   = $factory->getPossibleChildren($hierarchical_tracker);
        
        
        $actual_possible_children = $this->assertChildEquals($actual_possible_children, $possible_child_1);
        $actual_possible_children = $this->assertChildEquals($actual_possible_children, $possible_child_2);
        $this->assertEqual(count($actual_possible_children), 0);
    }
   
    public function testGetSimpleHierarchy() {
        $project_id = 110;
        $tracker = aTracker()->withId(2)->withProjectId($project_id)->build();
        
        $project_trackers = array(
            '1' => aTracker()->withId(1)->withName('Releases')->build(),
            '2' => aTracker()->withId(2)->withName('Sprints')->build(),
        );
        
        $tracker_factory = new MockTrackerFactory();
        $tracker_factory->expectOnce('getTrackersByGroupId', array($project_id));
        $tracker_factory->setReturnValue('getTrackersByGroupId', $project_trackers);
        
        $hierarchy_dar = array(
             array('child_id' => 2, 'parent_id' => 1)
        );
        
        $expected_hierarchy = 
            array('name' => 'Releases', 'children' => array(
                array('name' => 'Sprints', 'children' => array())
            ))
        ;
        
        $dao = new MockTracker_Hierarchy_Dao();
        $dao->setReturnValue('getHierarchy', $hierarchy_dar, array($tracker->getGroupId()));
        
        $factory = new Tracker_Hierarchy_HierarchicalTrackerFactory($tracker_factory, $dao);
        $actual_hierarchy = $factory->getHierarchy($tracker);
        $this->assertEqual($expected_hierarchy, $actual_hierarchy);
    }
    
    public function testGetHierarchyWithMultipleChildren() {
        $project_id = 110;
        $tracker = aTracker()->withId(3)->withProjectId($project_id)->build();
        
        $project_trackers = array(
            '1' => aTracker()->withId(1)->withName('Releases')->build(),
            '2' => aTracker()->withId(2)->withName('Sprints')->build(),
            '3' => aTracker()->withId(3)->withName('Epics')->build(),
        );
        
        $tracker_factory = new MockTrackerFactory();
        $tracker_factory->expectOnce('getTrackersByGroupId', array($project_id));
        $tracker_factory->setReturnValue('getTrackersByGroupId', $project_trackers);
        
        $hierarchy_dar = array(
             array('child_id' => 2, 'parent_id' => 1),
             array('child_id' => 3, 'parent_id' => 1)
        );
        
        $expected_hierarchy = 
            array('name' => 'Releases', 'children' => array(
                array('name' => 'Sprints', 'children' => array()),
                array('name' => 'Epics',   'children' => array())
            ))
        ;
        
        $dao = new MockTracker_Hierarchy_Dao();
        $dao->setReturnValue('getHierarchy', $hierarchy_dar, array($tracker->getGroupId()));
        
        $factory = new Tracker_Hierarchy_HierarchicalTrackerFactory($tracker_factory, $dao);
        $actual_hierarchy = $factory->getHierarchy($tracker);
        $this->assertEqual($expected_hierarchy, $actual_hierarchy);
    }
    
    public function testGetHierarchy() {
        $project_id = 110;

        $tracker = aTracker()->withId(3)->withProjectId($project_id)->build();
        
        $project_trackers = array(
            '1' => aTracker()->withId(1)->withName('Releases')->build(),
            '2' => aTracker()->withId(2)->withName('Sprints')->build(),
            '3' => aTracker()->withId(3)->withName('Stories')->build(),
            '4' => aTracker()->withId(4)->withName('Tasks')->build(),
            '5' => aTracker()->withId(5)->withName('Bugs')->build(),
        );
        $hierarchy_dar = array(
             array('child_id' => 2, 'parent_id' => 1),
             array('child_id' => 3, 'parent_id' => 2),
             array('child_id' => 4, 'parent_id' => 3),
             array('child_id' => 5, 'parent_id' => 2)
        );
        
        $tracker_factory = new MockTrackerFactory();
        $tracker_factory->expectOnce('getTrackersByGroupId', array($project_id));
        $tracker_factory->setReturnValue('getTrackersByGroupId', $project_trackers);
        
        $expected_hierarchy = 
            array('name' => 'Releases', 'children' => array(
                array('name' => 'Sprints', 'children' => array(
                    array('name' => 'Stories', 'children' => array(
                        array('name' => 'Tasks', 'children' => array())
                    )),
                    array('name' => 'Bugs', 'children' => array())
                ))
            ))
        ;
        
        $dao = new MockTracker_Hierarchy_Dao();
        $dao->setReturnValue('getHierarchy', $hierarchy_dar, array($tracker->getGroupId()));
        
        $factory = new Tracker_Hierarchy_HierarchicalTrackerFactory($tracker_factory, $dao);

        $actual_hierarchy = $factory->getHierarchy($tracker);
        $this->assertEqual($expected_hierarchy, $actual_hierarchy);
    }
    
    public function testGetRootTrackerIdFromHierarchyWithNoChildren() {
        $hierarchy_dar = array();

        $root_tracker_id = 3;
        $current_tracker_id = $root_tracker_id;

        $actual_root_tracker_id = $this->getRootTrackerId($hierarchy_dar, $current_tracker_id);
        $this->assertEqual($root_tracker_id, $actual_root_tracker_id);
    }
    
    public function testGetRootTrackerIdFromHierarchyWithOneChild() {
        $root_tracker_id = 1;
        $current_tracker_id = 2;
        
        $hierarchy_dar = array(
            array('child_id' => $current_tracker_id, 'parent_id' => $root_tracker_id)
        );

        $actual_root_tracker_id = $this->getRootTrackerId($hierarchy_dar, $current_tracker_id);
        $this->assertEqual($root_tracker_id, $actual_root_tracker_id);
    }
    
    public function testGetRootTrackerIdFromHierarchyWithMultipleChildren() {
        $root_tracker_id = 1;
        $current_tracker_id = 3;
        
        $hierarchy_dar = array(
            array('child_id' => 2,                   'parent_id' => 4),
            array('child_id' => $current_tracker_id, 'parent_id' => $root_tracker_id)
        );
        

        $actual_root_tracker_id = $this->getRootTrackerId($hierarchy_dar, $current_tracker_id);
        $this->assertEqual($root_tracker_id, $actual_root_tracker_id);
    }
    
    public function testGetRootTrackerIdFromHierarchyWithDeepHierarchy() {
        $root_tracker_id = 1;
        $current_tracker_id = 5;
        
        $hierarchy_dar = array(
             array('child_id' => 2,                   'parent_id' => $root_tracker_id),
             array('child_id' => 3,                   'parent_id' => 2),
             array('child_id' => 4,                   'parent_id' => 2),
             array('child_id' => $current_tracker_id, 'parent_id' => 3)
        );

        $actual_root_tracker_id = $this->getRootTrackerId($hierarchy_dar, $current_tracker_id);
        $this->assertEqual($root_tracker_id, $actual_root_tracker_id);
    }

    public function getRootTrackerId($hierarchy_dar, $current_tracker_id) {
        $dao = new MockTracker_Hierarchy_Dao();
        $factory = new Tracker_Hierarchy_HierarchicalTrackerFactory(new MockTrackerFactory(), $dao);
        return $factory->getRootTrackerId($hierarchy_dar, $current_tracker_id);
    }
}

?>
