<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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
require_once __DIR__.'/../../bootstrap.php';
class Tracker_HierarchyTest extends TuleapTestCase
{

    public function testWithEmptyHierarchGetLevelyShouldThrowExceptionForAnyTracker()
    {
        $hierarchy = new Tracker_Hierarchy();
        $this->expectException('Tracker_Hierarchy_NotInHierarchyException');
        $hierarchy->getLevel(1);
    }

    public function testGetLevelShouldReturn0ForTheTrackerWithId112()
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $level = $hierarchy->getLevel(112);
        $this->assertEqual(0, $level);
    }

    public function testGetLevelShouldReturn1ForTheTrackerWithId111()
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $level = $hierarchy->getLevel(111);
        $this->assertEqual(1, $level);
    }

    public function testWhenConstructedWithArrayOfArrayReverseSouldReturn0()
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(111, 112);
        $level = $hierarchy->getLevel(111);
        $this->assertEqual(0, $level);
    }

    public function testWithMultilevelHierarchyGetLevelShouldReturn2()
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(111, 112);
        $hierarchy->addRelationship(113, 111);
        $level = $hierarchy->getLevel(112);
        $this->assertEqual(2, $level);
    }

    public function testGetLevelShouldRaiseAnExceptionIfTheHierarchyIsCyclic()
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $hierarchy->addRelationship(112, 113);
        $hierarchy->addRelationship(113, 112);
        $this->expectException('Tracker_Hierarchy_CyclicHierarchyException');
        $hierarchy->getLevel(111);
    }

    public function testChildCannotBeItsParent()
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(111, 111);
        $this->expectException('Tracker_Hierarchy_CyclicHierarchyException');
        $hierarchy->getLevel(111);
    }

    public function testGetLevelShouldReturnOForEachRoots()
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $hierarchy->addRelationship(1002, 1050);
        $level = $hierarchy->getLevel(112);
        $this->assertEqual(0, $level);
        $level = $hierarchy->getLevel(1002);
        $this->assertEqual(0, $level);
    }

    public function itIsRootWhenItIsTheOnlyParent()
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $this->assertTrue($hierarchy->isRoot(112));
        $this->assertFalse($hierarchy->isRoot(111));
    }

    public function itIsRootWhenThereAreSeveralParents()
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $hierarchy->addRelationship(111, 110);
        $this->assertTrue($hierarchy->isRoot(112));
        $this->assertFalse($hierarchy->isRoot(111));
        $this->assertFalse($hierarchy->isRoot(110));
    }

    public function testIsRootShouldReturnFalseWhenTrackerIsNotInHierarchy()
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $this->assertFalse($hierarchy->isRoot(666));
    }

    public function ItShouldBeChild()
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $this->assertTrue($hierarchy->isChild(112, 111));
    }

    public function ItShouldNotBeChild()
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $this->assertFalse($hierarchy->isChild(112, 666));
        $this->assertFalse($hierarchy->isChild(111, 112));
    }

    public function ItFlattenTheInternalHierarchy()
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(111, 112);
        $hierarchy->addRelationship(112, 113);

        $this->assertEqual($hierarchy->flatten(), array(111,112,113));
    }

    public function ItFlattenTheInternalHierarchyButLonelyTrackerIsAlone()
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(111, 0);
        $this->assertEqual($hierarchy->flatten(), array(111));
    }
}

class Tracker_Hierarchy_GetParentTest extends TuleapTestCase
{

    public function itReturnsParent()
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(111, 112);
        $hierarchy->addRelationship(112, 113);

        $this->assertEqual(null, $hierarchy->getParent(111));
        $this->assertEqual(111, $hierarchy->getParent(112));
        $this->assertEqual(112, $hierarchy->getParent(113));
    }

    public function itReturnsNullWhenNoHierarchy()
    {
        $hierarchy = new Tracker_Hierarchy();

        $this->assertEqual(null, $hierarchy->getParent(111));
    }
}

class Tracker_Hierarchy_SortTest extends TuleapTestCase
{

    public function itReturnsEmptyArrayWhenNoHierarchy()
    {
        $hierarchy = new Tracker_Hierarchy();

        $this->assertEqual(array(), $hierarchy->sortTrackerIds(array()));
    }

    public function itReturnsTheTwoTrackersWhenJustOneRelationShip()
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 113);
        $this->assertEqual(array(112, 113), $hierarchy->sortTrackerIds(array(113, 112)));
    }

    public function itReturnsTrackersFromTopToBottom()
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 113);
        $hierarchy->addRelationship(111, 112);

        $this->assertEqual(array(111, 112, 113), $hierarchy->sortTrackerIds(array(112, 113, 111)));
    }

    public function itReturnsTrackersFromTopToBottomAndTrackerNotInHierarchyAtTheEnd()
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 113);
        $hierarchy->addRelationship(111, 112);

        // Sorts in PHP are not expected to be stable (https://bugs.php.net/bug.php?id=69158)
        // and since trackers 666, 667 and 668 are not hierarchy
        // they can be placed at any positions at the end
        // That means we can have something like [111, 112, 113, 667, 668, 666]
        // or [111, 112, 113, 668, 666, 667] or …
        $sorted_tracker_ids = $hierarchy->sortTrackerIds([667, 111, 666, 112, 668, 113]);
        $this->assertEqual([111, 112, 113], array_slice($sorted_tracker_ids, 0, 3));
        foreach ([666, 667, 668] as $not_in_hierarchy_tracker_id) {
            $this->assertTrue(
                in_array(
                    $not_in_hierarchy_tracker_id,
                    array_slice($sorted_tracker_ids, 3, 3),
                    true
                )
            );
        }
    }
}

class Tracker_Hierarchy_GetLastLevelTest extends TuleapTestCase
{
    public function itReturnsAnEpmtyArrayIfNoRelationshipsExist()
    {
        $hierarchy = new Tracker_Hierarchy();

        $this->assertEqual(array(), $hierarchy->getLastLevelTrackerIds());
    }

    public function itReturnsLastlevelTrackerIds()
    {
        $hierarchy = new Tracker_Hierarchy();

        $grand_pa = 11;
        $papa     = 222;
        $child    = 3333;

        $grand_uncle = 55;
        $uncle       = 444;

        $hierarchy->addRelationship($grand_pa, $papa);
        $hierarchy->addRelationship($grand_pa, $uncle);
        $hierarchy->addRelationship($papa, $child);
        $hierarchy->addRelationship(null, $grand_uncle);

        $expected = array($grand_uncle, $uncle, $child);
        $result   = $hierarchy->getLastLevelTrackerIds();

        sort($expected);
        sort($result);

        $this->assertEqual($expected, $result);
    }
}
