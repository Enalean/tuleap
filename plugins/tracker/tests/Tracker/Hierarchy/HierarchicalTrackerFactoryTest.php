<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\Tracker\Hierarchy\HierarchyDAO;

require_once __DIR__.'/../../bootstrap.php';

Mock::generate('Project');
Mock::generate('TrackerFactory');

class HierarchicalTrackerFactoryTest extends TuleapTestCase
{

    function testGetWithChildren()
    {
        $tracker = aTracker()->withId(1)->build();

        $dao          = Mockery::mock(HierarchyDAO::class);
        $children_ids = [2, 3];
        $dao->shouldReceive('getChildren')->with($tracker->getId())->andReturn($children_ids);

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

    private function assertChildEquals($children, $tracker)
    {
        $child = array_shift($children);
        $this->assertEqual($child, $tracker);
        return $children;
    }

    public function testGetPossibleChildren()
    {
        $dao = Mockery::mock(HierarchyDAO::class);
        $dao->shouldReceive('searchAncestorIds')->with(1)->andReturn([4])->once();

        $project_id = 100;
        $project    = new MockProject();
        $project->setReturnValue('getId', $project_id);
        $tracker    = aTracker()->withId(1)->withProject($project)->build();
        $hierarchical_tracker = new Tracker_Hierarchy_HierarchicalTracker($tracker, array());

        $possible_child_1 = aTracker()->withId(2)->build();
        $possible_child_2 = aTracker()->withId(3)->build();
        $ancestor         = aTracker()->withId(4)->build();

        $project_trackers = array(
            1 => $tracker,
            2 => $possible_child_1,
            3 => $possible_child_2,
            4 => $ancestor
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

    private function getHierarchyAsTreeNode($hierarchy)
    {
        $node = new TreeNode();
        if (isset($hierarchy['children'])) {
            $node->setData(array('name' => $hierarchy['name'], 'id' => $hierarchy['id']));
            $node->setId($hierarchy['id']);
            $hierarchy = $hierarchy['children'];
        } else {
            $node->setId('root');
        }
        foreach ($hierarchy as $item) {
            $node->addChild($this->getHierarchyAsTreeNode($item));
        }
        return $node;
    }

    public function testGetDeepHierarchy()
    {
        $project_id = 110;
        $project    = new MockProject();
        $project->setReturnValue('getID', $project_id);
        $tracker    = aTracker()->withId(5)->withProject($project)->build();

        $project_trackers = array(
            '1' => aTracker()->withId(1)->withName('Releases')->build(),
            '2' => aTracker()->withId(2)->withName('Sprints')->build(),
            '3' => aTracker()->withId(3)->withName('Stories')->build(),
            '4' => aTracker()->withId(4)->withName('Tasks')->build(),
            '5' => aTracker()->withId(5)->withName('Bugs')->build(),
            '6' => aTracker()->withId(6)->withName('Documents')->build()
        );

        $hierarchy_dar = array(
             array('child_id' => 2, 'parent_id' => 1),
             array('child_id' => 3, 'parent_id' => 2),
             array('child_id' => 4, 'parent_id' => 3),
             array('child_id' => 5, 'parent_id' => 2)
        );

        $expected_hierarchy = $this->getHierarchyAsTreeNode(array(
            array('name' => 'Releases', 'id'=>1, 'children' => array(
                array('name' => 'Sprints', 'id'=>2, 'children' => array(
                    array('name' => 'Stories', 'id'=>3, 'children' => array(
                        array('name' => 'Tasks', 'id'=>4, 'children' => array())
                    )),
                    array('name' => 'Bugs', 'id'=>5, 'children' => array())
                ))
            )),
            array('name' => 'Documents', 'id' => 6, 'children' => array())
        ));

        $dao             = $this->aMockDaoWith($project_id, $hierarchy_dar);
        $tracker_factory = $this->aMockTrackerFactoryWith($project_id, $project_trackers);
        $factory         = new Tracker_Hierarchy_HierarchicalTrackerFactory($tracker_factory, $dao);

        $this->assertEqual($expected_hierarchy->__toString(), $factory->getHierarchy($tracker)->__toString());
    }

    public function itCanReturnTheListOfTrackersInHierarchyByParentId()
    {
        $hierarchy_dar = array(
             array('child_id' => 2, 'parent_id' => 1),
             array('child_id' => 3, 'parent_id' => 2),
             array('child_id' => 4, 'parent_id' => 3),
             array('child_id' => 5, 'parent_id' => 2)
        );
        $project_trackers = array(
            '1' => aTracker()->withId(1)->withName('Releases')->build(),
            '2' => aTracker()->withId(2)->withName('Sprints')->build(),
            '3' => aTracker()->withId(3)->withName('Stories')->build(),
            '4' => aTracker()->withId(4)->withName('Tasks')->build(),
            '5' => aTracker()->withId(5)->withName('Bugs')->build(),
            '6' => aTracker()->withId(6)->withName('Documents')->build()
        );
        $project_id = 100;
        $dao             = $this->aMockDaoWith($project_id, $hierarchy_dar);
        $tracker_factory = new MockTrackerFactory();
        $factory = new Tracker_Hierarchy_HierarchicalTrackerFactory($tracker_factory, $dao);
        $expected = array(
            1 => array(2),
            2 => array(3, 5),
            3 => array(4),
            4 => array(),
            5 => array(),
            6 => array(),
            'root'=>array(1, 6)
        );
        $this->assertEqual($expected, $factory->getChildrenMapFromDar($hierarchy_dar, $project_trackers));
    }

    public function itCanMoveATrackerAndSonsToAnothersTrackerNotInTheSameBranchAtTheSameTime()
    {
        $project_id = 110;
        $project    = new MockProject();
        $project->setReturnValue('getID', $project_id);

        $project_trackers = array(
            '117' => aTracker()->withId(117)->withName('Project')->build(),
            '118' => aTracker()->withId(118)->withName('Releases')->build(),
            '120' => aTracker()->withId(120)->withName('Stories')->withProject($project)->build(),
            '121' => aTracker()->withId(121)->withName('Tasks')->build(),
            '122' => aTracker()->withId(122)->withName('Bugs')->build(),
            '119' => aTracker()->withId(119)->withName('Epics')->build()
        );
        $tracker = $project_trackers['120'];

        $hierarchy_dar = array(
             array('parent_id' => 120, 'child_id' => 118),
             array('parent_id' => 120, 'child_id' => 117),
             array('parent_id' => 119, 'child_id' => 120),
             array('parent_id' => 120, 'child_id' => 122)
        );

        $expected_hierarchy = $this->getHierarchyAsTreeNode(array(
            array('name' => 'Epics', 'id' => 119, 'children' => array(
                array('name' => 'Stories', 'id' => 120, 'children' => array(
                    array('name' => 'Releases', 'id' => 118, 'children' => array()),
                    array('name' => 'Projects', 'id' => 117, 'children' => array()),
                    array('name' => 'Bugs',     'id' => 122, 'children' => array())
                ))
            )),
            array('name' => 'Tasks', 'id' => 121, 'children' => array())
        ));

        $dao             = $this->aMockDaoWith($project_id, $hierarchy_dar);
        $tracker_factory = $this->aMockTrackerFactoryWith($project_id, $project_trackers);
        $factory         = new Tracker_Hierarchy_HierarchicalTrackerFactory($tracker_factory, $dao);

        $this->assertEqual($expected_hierarchy->__toString(), $factory->getHierarchy($tracker)->__toString());
    }

    public function testGetRootTrackerIdFromHierarchyWithNoChildren()
    {
        $hierarchy_dar = array();

        $expected_root_tracker_id = 3;
        $current_tracker_id = $expected_root_tracker_id;

        $actual_root_tracker_id = $this->getRootTrackerId($hierarchy_dar, $current_tracker_id);
        $this->assertEqual($expected_root_tracker_id, $actual_root_tracker_id);
    }

    public function testGetRootTrackerIdFromHierarchyWithOneChild()
    {
        $expected_root_tracker_id = 1;
        $current_tracker_id = 2;

        $hierarchy_dar = array(
            array('child_id' => $current_tracker_id, 'parent_id' => $expected_root_tracker_id));

        $actual_root_tracker_id = $this->getRootTrackerId($hierarchy_dar, $current_tracker_id);
        $this->assertEqual($expected_root_tracker_id, $actual_root_tracker_id);
    }

    public function testGetRootTrackerIdFromHierarchyWithMultipleChildren()
    {
        $expected_root_tracker_id = 1;
        $current_tracker_id = 3;

        $hierarchy_dar = array(
            array('child_id' => 2,                   'parent_id' => 4),
            array('child_id' => $current_tracker_id, 'parent_id' => $expected_root_tracker_id));

        $actual_root_tracker_id = $this->getRootTrackerId($hierarchy_dar, $current_tracker_id);
        $this->assertEqual($expected_root_tracker_id, $actual_root_tracker_id);
    }

    public function testGetRootTrackerIdFromHierarchyWithDeepHierarchy()
    {
        $expected_root_tracker_id = 1;
        $current_tracker_id = 5;

        $hierarchy_dar = array(
             array('child_id' => 2,                   'parent_id' => $expected_root_tracker_id),
             array('child_id' => 3,                   'parent_id' => 2),
             array('child_id' => 4,                   'parent_id' => 2),
             array('child_id' => $current_tracker_id, 'parent_id' => 3));

        $actual_root_tracker_id = $this->getRootTrackerId($hierarchy_dar, $current_tracker_id);
        $this->assertEqual($expected_root_tracker_id, $actual_root_tracker_id);
    }

    private function getRootTrackerId($hierarchy_dar, $current_tracker_id)
    {
        $dao = Mockery::spy(HierarchyDAO::class);
        $factory = new Tracker_Hierarchy_HierarchicalTrackerFactory(new MockTrackerFactory(), $dao);
        return $factory->getRootTrackerId($hierarchy_dar, $current_tracker_id);
    }

    private function aMockTrackerFactoryWith($project_id, $project_trackers)
    {
        $tracker_factory = new MockTrackerFactory();
        $tracker_factory->expectOnce('getTrackersByGroupId', array($project_id));
        $tracker_factory->setReturnValue('getTrackersByGroupId', $project_trackers);
        return $tracker_factory;
    }

    private function aMockDaoWith($project_id, $hierarchy_dar)
    {
        $dao = Mockery::spy(HierarchyDAO::class);
        $dao->shouldReceive('searchParentChildAssociations')->with($project_id)->andReturn($hierarchy_dar);
        return $dao;
    }
}
