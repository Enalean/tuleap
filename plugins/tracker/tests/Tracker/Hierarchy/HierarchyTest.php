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
require_once dirname(__FILE__) .'/../../../include/Tracker/Hierarchy/Hierarchy.class.php';

class Tracker_HierarchyTest extends TuleapTestCase {
    
    public function testWithEmptyHierarchGetLevelyShouldThrowExceptionForAnyTracker() {
        $hierarchy = new Tracker_Hierarchy();
        $this->expectException('Tracker_Hierarchy_NotInHierarchyException');
        $hierarchy->getLevel(1);
    }
    
    public function testGetLevelShouldReturn0ForTheTrackerWithId112() {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $level = $hierarchy->getLevel(112);
        $this->assertEqual(0, $level);
    }
    
    public function testGetLevelShouldReturn1ForTheTrackerWithId111() {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $level = $hierarchy->getLevel(111);
        $this->assertEqual(1, $level);
    }
    
    public function testWhenConstructedWithArrayOfArrayReverseSouldReturn0() {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(111, 112);
        $level = $hierarchy->getLevel(111);
        $this->assertEqual(0, $level);
    }
    
    public function testWithMultilevelHierarchyGetLevelShouldReturn2() {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(111, 112);
        $hierarchy->addRelationship(113, 111);
        $level = $hierarchy->getLevel(112);
        $this->assertEqual(2, $level);
    }
    
    public function testGetLevelShouldRaiseAnExceptionIfTheHierarchyIsCyclic() {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $hierarchy->addRelationship(112, 113);
        $hierarchy->addRelationship(113, 112);
        $this->expectException('Tracker_Hierarchy_CyclicHierarchyException');
        $hierarchy->getLevel(111);
    }
    
    public function testChildCannotBeItsParent() {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(111, 111);
        $this->expectException('Tracker_Hierarchy_CyclicHierarchyException');
        $hierarchy->getLevel(111);
    }
    
    public function testGetLevelShouldReturnOForEachRoots() {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $hierarchy->addRelationship(1002, 1050);
        $level = $hierarchy->getLevel(112);
        $this->assertEqual(0, $level);
        $level = $hierarchy->getLevel(1002);
        $this->assertEqual(0, $level);
    }

    public function itIsRootWhenItIsTheOnlyParent() {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $this->assertTrue($hierarchy->isRoot(112));
        $this->assertFalse($hierarchy->isRoot(111));
    }

    public function itIsRootWhenThereAreSeveralParents() {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $hierarchy->addRelationship(111, 110);
        $this->assertTrue($hierarchy->isRoot(112));
        $this->assertFalse($hierarchy->isRoot(111));
        $this->assertFalse($hierarchy->isRoot(110));
    }

    public function ItShouldBeChild() {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $this->assertTrue($hierarchy->isChild(112, 111));
    }
    
    public function ItShouldNotBeChild() {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $this->assertFalse($hierarchy->isChild(112, 666));
        $this->assertFalse($hierarchy->isChild(111, 112));
    }

    public function ItFlattenTheInternalHierarchy() {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(111, 112);
        $hierarchy->addRelationship(112, 113);

        $this->assertEqual($hierarchy->flatten(), array(111,112,113));
    }

    public function ItFlattenTheInternalHierarchyButLonelyTrackerIsAlone() {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(111, 0);
        $this->assertEqual($hierarchy->flatten(), array(111));
    }
}
?>
