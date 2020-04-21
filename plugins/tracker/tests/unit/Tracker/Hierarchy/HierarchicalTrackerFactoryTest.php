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

final class HierarchicalTrackerFactoryTest extends \PHPUnit\Framework\TestCase //phpcs:ignore: PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testGetWithChildren(): void
    {
        $tracker = $this->mockTrackerWithId(1, 'Name');

        $dao          = Mockery::mock(HierarchyDAO::class);
        $children_ids = [2, 3];
        $dao->shouldReceive('getChildren')->with($tracker->getId())->andReturn($children_ids);

        $child1          = $this->mockTrackerWithId(2, 'Name');
        $child2          = $this->mockTrackerWithId(3, 'Name');
        $tracker_factory = \Mockery::spy(\TrackerFactory::class);
        $tracker_factory->shouldReceive('getTrackerById')->with(2)->andReturns($child1);
        $tracker_factory->shouldReceive('getTrackerById')->with(3)->andReturns($child2);

        $factory              = new Tracker_Hierarchy_HierarchicalTrackerFactory($tracker_factory, $dao);
        $hierarchical_tracker = $factory->getWithChildren($tracker);

        $children = $hierarchical_tracker->getChildren();
        $children = $this->assertChildEquals($children, $child1);
        $children = $this->assertChildEquals($children, $child2);
        $this->assertCount(0, $children);
    }

    private function assertChildEquals($children, $tracker)
    {
        $child = array_shift($children);
        $this->assertEquals($child, $tracker);

        return $children;
    }

    public function testGetPossibleChildren(): void
    {
        $dao = Mockery::mock(HierarchyDAO::class);
        $dao->shouldReceive('searchAncestorIds')->with(1)->andReturn([4])->once();

        $project_id = 100;
        $project    = \Mockery::spy(\Project::class);
        $project->shouldReceive('getId')->andReturns($project_id);
        $tracker = $this->mockTrackerWithId(1, 'Name');
        $tracker->shouldReceive("getProject")->andReturn($project);
        $hierarchical_tracker = new Tracker_Hierarchy_HierarchicalTracker($tracker, []);

        $possible_child_1 = $this->mockTrackerWithId(2, 'Name');
        $possible_child_2 = $this->mockTrackerWithId(3, 'Name');
        $ancestor         = $this->mockTrackerWithId(4, 'Name');

        $project_trackers = [
            1 => $tracker,
            2 => $possible_child_1,
            3 => $possible_child_2,
            4 => $ancestor
        ];

        $tracker_factory = \Mockery::spy(\TrackerFactory::class);
        $tracker_factory->shouldReceive('getTrackersByGroupId')->with($project_id)->andReturns($project_trackers);

        $factory = new Tracker_Hierarchy_HierarchicalTrackerFactory($tracker_factory, $dao);

        $actual_possible_children = $factory->getPossibleChildren($hierarchical_tracker);

        $actual_possible_children = $this->assertChildEquals($actual_possible_children, $possible_child_1);
        $actual_possible_children = $this->assertChildEquals($actual_possible_children, $possible_child_2);
        $this->assertCount(0, $actual_possible_children);
    }

    private function getHierarchyAsTreeNode($hierarchy): \TreeNode
    {
        $node = new TreeNode();
        if (isset($hierarchy['children'])) {
            $node->setData(['name' => $hierarchy['name'], 'id' => $hierarchy['id']]);
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

    public function testGetDeepHierarchy(): void
    {
        $project_id = 110;
        $project    = \Mockery::spy(\Project::class);
        $project->shouldReceive('getID')->andReturns($project_id);
        $tracker = $this->mockTrackerWithId(5, 'Name');
        $tracker->shouldReceive('getProject')->andReturn($project);
        $tracker->shouldReceive('getGroupId')->andReturn($project_id);

        $project_trackers = [
            '1' => $this->mockTrackerWithId(1, 'Releases'),
            '2' => $this->mockTrackerWithId(2, 'Sprints'),
            '3' => $this->mockTrackerWithId(3, 'Stories'),
            '4' => $this->mockTrackerWithId(4, 'Tasks'),
            '5' => $this->mockTrackerWithId(5, 'Bugs'),
            '6' => $this->mockTrackerWithId(6, 'Documents')
        ];

        $hierarchy_dar = [
            ['child_id' => 2, 'parent_id' => 1],
            ['child_id' => 3, 'parent_id' => 2],
            ['child_id' => 4, 'parent_id' => 3],
            ['child_id' => 5, 'parent_id' => 2]
        ];

        $expected_hierarchy = $this->getHierarchyAsTreeNode(
            [
                [
                    'name'     => 'Releases',
                    'id'       => 1,
                    'children' => [
                        [
                            'name'     => 'Sprints',
                            'id'       => 2,
                            'children' => [
                                [
                                    'name'     => 'Stories',
                                    'id'       => 3,
                                    'children' => [
                                        ['name' => 'Tasks', 'id' => 4, 'children' => []]
                                    ]
                                ],
                                ['name' => 'Bugs', 'id' => 5, 'children' => []]
                            ]
                        ]
                    ]
                ],
                ['name' => 'Documents', 'id' => 6, 'children' => []]
            ]
        );

        $dao             = $this->aMockDaoWith($project_id, $hierarchy_dar);
        $tracker_factory = $this->aMockTrackerFactoryWith($project_id, $project_trackers);
        $factory         = new Tracker_Hierarchy_HierarchicalTrackerFactory($tracker_factory, $dao);

        $this->assertEquals($expected_hierarchy->__toString(), $factory->getHierarchy($tracker)->__toString());
    }

    public function testItCanReturnTheListOfTrackersInHierarchyByParentId(): void
    {
        $hierarchy_dar    = [
            ['child_id' => 2, 'parent_id' => 1],
            ['child_id' => 3, 'parent_id' => 2],
            ['child_id' => 4, 'parent_id' => 3],
            ['child_id' => 5, 'parent_id' => 2]
        ];
        $project_trackers = [
            '1' => $this->mockTrackerWithId(1, 'Releases'),
            '2' => $this->mockTrackerWithId(2, 'Sprints'),
            '3' => $this->mockTrackerWithId(3, 'Stories'),
            '4' => $this->mockTrackerWithId(4, 'Tasks'),
            '5' => $this->mockTrackerWithId(5, 'Bugs'),
            '6' => $this->mockTrackerWithId(6, 'Documents')
        ];
        $project_id       = 100;
        $dao              = $this->aMockDaoWith($project_id, $hierarchy_dar);
        $tracker_factory  = \Mockery::spy(\TrackerFactory::class);
        $factory          = new Tracker_Hierarchy_HierarchicalTrackerFactory($tracker_factory, $dao);
        $expected         = [
            1      => [2],
            2      => [3, 5],
            3      => [4],
            4      => [],
            5      => [],
            6      => [],
            'root' => [1, 6]
        ];
        $this->assertEquals($expected, $factory->getChildrenMapFromDar($hierarchy_dar, $project_trackers));
    }

    public function testItCanMoveATrackerAndSonsToAnothersTrackerNotInTheSameBranchAtTheSameTime(): void
    {
        $project_id = 110;
        $project    = \Mockery::spy(\Project::class);
        $project->shouldReceive('getID')->andReturns($project_id);

        $story_tracker = $this->mockTrackerWithId(120, 'Stories');
        $story_tracker->shouldReceive("getProject")->andReturn($project);
        $story_tracker->shouldReceive("getGroupId")->andReturn($project_id);
        $project_trackers = [
            '117' => $this->mockTrackerWithId(117, 'Project'),
            '118' => $this->mockTrackerWithId(118, 'Releases'),
            '120' => $story_tracker,
            '121' => $this->mockTrackerWithId(121, 'Tasks'),
            '122' => $this->mockTrackerWithId(122, 'Bugs'),
            '119' => $this->mockTrackerWithId(119, 'Epics')
        ];
        $tracker          = $project_trackers['120'];

        $hierarchy_dar = [
            ['parent_id' => 120, 'child_id' => 118],
            ['parent_id' => 120, 'child_id' => 117],
            ['parent_id' => 119, 'child_id' => 120],
            ['parent_id' => 120, 'child_id' => 122]
        ];

        $expected_hierarchy = $this->getHierarchyAsTreeNode(
            [
                [
                    'name'     => 'Epics',
                    'id'       => 119,
                    'children' => [
                        [
                            'name'     => 'Stories',
                            'id'       => 120,
                            'children' => [
                                ['name' => 'Releases', 'id' => 118, 'children' => []],
                                ['name' => 'Projects', 'id' => 117, 'children' => []],
                                ['name' => 'Bugs', 'id' => 122, 'children' => []]
                            ]
                        ]
                    ]
                ],
                ['name' => 'Tasks', 'id' => 121, 'children' => []]
            ]
        );

        $dao             = $this->aMockDaoWith($project_id, $hierarchy_dar);
        $tracker_factory = $this->aMockTrackerFactoryWith($project_id, $project_trackers);
        $factory         = new Tracker_Hierarchy_HierarchicalTrackerFactory($tracker_factory, $dao);

        $this->assertEquals($expected_hierarchy->__toString(), $factory->getHierarchy($tracker)->__toString());
    }

    public function testGetRootTrackerIdFromHierarchyWithNoChildren(): void
    {
        $hierarchy_dar = [];

        $expected_root_tracker_id = 3;
        $current_tracker_id       = $expected_root_tracker_id;

        $actual_root_tracker_id = $this->getRootTrackerId($hierarchy_dar, $current_tracker_id);
        $this->assertEquals($expected_root_tracker_id, $actual_root_tracker_id);
    }

    public function testGetRootTrackerIdFromHierarchyWithOneChild(): void
    {
        $expected_root_tracker_id = 1;
        $current_tracker_id       = 2;

        $hierarchy_dar = [
            ['child_id' => $current_tracker_id, 'parent_id' => $expected_root_tracker_id]
        ];

        $actual_root_tracker_id = $this->getRootTrackerId($hierarchy_dar, $current_tracker_id);
        $this->assertEquals($expected_root_tracker_id, $actual_root_tracker_id);
    }

    public function testGetRootTrackerIdFromHierarchyWithMultipleChildren(): void
    {
        $expected_root_tracker_id = 1;
        $current_tracker_id       = 3;

        $hierarchy_dar = [
            ['child_id' => 2, 'parent_id' => 4],
            ['child_id' => $current_tracker_id, 'parent_id' => $expected_root_tracker_id]
        ];

        $actual_root_tracker_id = $this->getRootTrackerId($hierarchy_dar, $current_tracker_id);
        $this->assertEquals($expected_root_tracker_id, $actual_root_tracker_id);
    }

    public function testGetRootTrackerIdFromHierarchyWithDeepHierarchy(): void
    {
        $expected_root_tracker_id = 1;
        $current_tracker_id       = 5;

        $hierarchy_dar = [
            ['child_id' => 2, 'parent_id' => $expected_root_tracker_id],
            ['child_id' => 3, 'parent_id' => 2],
            ['child_id' => 4, 'parent_id' => 2],
            ['child_id' => $current_tracker_id, 'parent_id' => 3]
        ];

        $actual_root_tracker_id = $this->getRootTrackerId($hierarchy_dar, $current_tracker_id);
        $this->assertEquals($expected_root_tracker_id, $actual_root_tracker_id);
    }

    private function getRootTrackerId($hierarchy_dar, $current_tracker_id)
    {
        $dao     = Mockery::spy(HierarchyDAO::class);
        $factory = new Tracker_Hierarchy_HierarchicalTrackerFactory(\Mockery::spy(\TrackerFactory::class), $dao);

        return $factory->getRootTrackerId($hierarchy_dar, $current_tracker_id);
    }

    private function aMockTrackerFactoryWith($project_id, $project_trackers)
    {
        $tracker_factory = \Mockery::spy(\TrackerFactory::class);
        $tracker_factory->shouldReceive('getTrackersByGroupId')->with($project_id)->once()->andReturns(
            $project_trackers
        );

        return $tracker_factory;
    }

    private function aMockDaoWith($project_id, $hierarchy_dar)
    {
        $dao = Mockery::spy(HierarchyDAO::class);
        $dao->shouldReceive('searchParentChildAssociations')->with($project_id)->andReturn($hierarchy_dar);

        return $dao;
    }

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private function mockTrackerWithId(int $id, string $name)
    {
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn($id);
        $tracker->shouldReceive('getName')->andReturn($name);

        return $tracker;
    }
}
