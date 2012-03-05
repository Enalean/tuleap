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
        $dao     = new MockTracker_Hierarchy_Dao();
        $dao->setReturnValue('searchTrackerHierarchy', array());
        $factory = new Tracker_HierarchyFactory($dao);
        $this->assertIsA($factory->getHierarchy(), 'Tracker_Hierarchy');
    }
    
    public function testFactoryShouldReturnManyDifferentHierarchies() {
        $dao     = new MockTracker_Hierarchy_Dao();
        $dao->setReturnValue('searchTrackerHierarchy', array());
        $factory = new Tracker_HierarchyFactory($dao);
        
        $h1 = $factory->getHierarchy();
        $h2 = $factory->getHierarchy();
        
        $this->assertTrue($h1 !== $h2);
    }
    
    public function testFactoryShouldCallTheDatabaseToBuildHierarchy() {
        $dao     = new MockTracker_Hierarchy_Dao();
        $dao->setReturnValue('searchTrackerHierarchy', array());
        $factory = new Tracker_HierarchyFactory($dao);
        
        $dao->expectOnce('searchTrackerHierarchy');
        
        $factory->getHierarchy();
    }
    
    public function testFactoryShouldReturnARealHierarchyAccordingToDatabase() {
        $dao     = new MockTracker_Hierarchy_Dao();
        $factory = new Tracker_HierarchyFactory($dao);
        
        $dao->setReturnValue('searchTrackerHierarchy', TestHelper::arrayToDar(array('parent_id' => 111, 'child_id' => 112)));
        
        $hierarchy = $factory->getHierarchy();
        $this->assertEqual($hierarchy->getLevel(112), 1);
    }
}
?>
