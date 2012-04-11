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

require_once dirname(__FILE__) .'/../../../include/Tracker/Hierarchy/HierarchyFactory.class.php';

Mock::generate('Tracker_Hierarchy_Dao');

class Tracker_HierarchyFactoryTest extends UnitTestCase {

    public function testFactoryShouldCreateAHierarchy() {
        $factory = $this->GivenAHierarchyFactory();
        $this->assertIsA($factory->getHierarchy(), 'Tracker_Hierarchy');
    }
    
    public function testFactoryShouldReturnManyDifferentHierarchies() {
        $factory = $this->GivenAHierarchyFactory();
        
        $h1 = $factory->getHierarchy();
        $h2 = $factory->getHierarchy();
        
        $this->assertTrue($h1 !== $h2);
    }
    
    public function testFactoryShouldCallTheDatabaseToBuildHierarchy() {
        $dao = new MockTracker_Hierarchy_Dao();
        $dao->setReturnValue('searchTrackerHierarchy', array());
        $dao->expectOnce('searchTrackerHierarchy');
        
        $factory = $this->GivenAHierarchyFactory($dao);
        $factory->getHierarchy(array(111));
    }
    
    public function testFactoryShouldReturnARealHierarchyAccordingToDatabase() {
        $dao     = new MockTracker_Hierarchy_Dao();
        $dao->setReturnValue('searchTrackerHierarchy', TestHelper::arrayToDar(array('parent_id' => 111, 'child_id' => 112)));
        
        $factory = $this->GivenAHierarchyFactory($dao);
        
        $hierarchy = $factory->getHierarchy(array(111));
        $this->assertEqual($hierarchy->getLevel(112), 1);
    }
    
    public function testFactoryShouldReturnFullHierarchy() {
        /*
          111
          +- 112
             +- 113
                +- 114
        */
        $dao = $this->GivenADaoThatContainsFullHierarchy();
        $factory = $this->GivenAHierarchyFactory($dao);
        
        $hierarchy = $factory->getHierarchy(array(111, 114));
        $this->assertEqual($hierarchy->getLevel(114), 3);
    }
    
    private function GivenADaoThatContainsFullHierarchy() {
        $dao     = new MockTracker_Hierarchy_Dao();
        $dar1 = TestHelper::arrayToDar(
            array('parent_id' => 111, 'child_id' => 112),
            array('parent_id' => 113, 'child_id' => 114)
        );
        $dao->setReturnValue('searchTrackerHierarchy', $dar1, array(array(111, 114)));
        $dar2 = TestHelper::arrayToDar(
            array('parent_id' => 111, 'child_id' => 112),
            array('parent_id' => 112, 'child_id' => 113),
            array('parent_id' => 113, 'child_id' => 114)
        );
        $dao->setReturnValue('searchTrackerHierarchy', $dar2, array(array(112, 113)));
        return $dao;
    }
    
    private function GivenAHierarchyFactory($dao = null) {
        if (!$dao) {
            $dao = new MockTracker_Hierarchy_Dao();
            $dao->setReturnValue('searchTrackerHierarchy', array());
        }
        return new Tracker_HierarchyFactory($dao, mock('TrackerFactory'));
    }
}
?>
