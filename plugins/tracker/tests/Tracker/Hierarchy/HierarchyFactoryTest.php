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

require_once dirname(__FILE__).'/../../../include/constants.php';
require_once TRACKER_BASE_DIR.'/Tracker/Hierarchy/HierarchyFactory.class.php';
require_once TRACKER_BASE_DIR.'/Tracker/TrackerFactory.class.php';
require_once TRACKER_BASE_DIR.'/../tests/builders/anArtifact.php';
require_once dirname(__FILE__).'/../../builders/aMockTracker.php';

Mock::generate('Tracker_Hierarchy_Dao');

class Tracker_HierarchyFactoryTest extends TuleapTestCase {

    public function itReturnsItselfWhenThereIsNoHierarchyAssociatedToTracker() {
        $factory = $this->GivenAHierarchyFactory();

        $lonely_tracker                = array(115);
        $lonely_tracker_hierarchy_list = $factory->getHierarchy($lonely_tracker)->flatten();

        $this->assertEqual($lonely_tracker_hierarchy_list, $lonely_tracker);
    }

    public function itRetrievesTheChildrenOfAGivenTracker() {
        $hierarchy_dao     = mock('Tracker_Hierarchy_Dao');
        $tracker_factory   = mock('TrackerFactory');
        $hierarchy_factory = new Tracker_HierarchyFactory($hierarchy_dao, $tracker_factory, mock('Tracker_ArtifactFactory'));

        $tracker_id = 1;
        $child_ids  = array(array('id' => 11), array('id' => 12));

        $child_1 = mock('Tracker');
        $child_2 = mock('Tracker');

        stub($hierarchy_dao)->searchChildTrackerIds($tracker_id)->returns($child_ids);
        stub($tracker_factory)->getTrackerById(11)->returns($child_1);
        stub($tracker_factory)->getTrackerById(12)->returns($child_2);

        $expected_children = array($child_1, $child_2);
        $actual_children   = $hierarchy_factory->getChildren($tracker_id);

        $this->assertEqual($actual_children, $expected_children);
    }

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

    public function testDuplicateHierarchy() {
        $dao = $this->GivenADaoThatContainsOneFullHierrachy();

        $factory = $this->GivenAHierarchyFactory($dao);

        $tracker_mapping = array(
            '111' => '211',
            '112' => '212',
            '113' => '213',
            '114' => '214',
        );

        $dao->expectCallCount('duplicate', 3, 'Method duplicate from Dao should be called 3 times.');

        $factory->duplicate($tracker_mapping);

    }

    private function GivenADaoThatContainsOneFullHierrachy() {
        $dao = new MockTracker_Hierarchy_Dao();
        $dar = TestHelper::arrayToDar(
            array('parent_id' => 111, 'child_id' => 112),
            array('parent_id' => 112, 'child_id' => 113),
            array('parent_id' => 113, 'child_id' => 114)
        );
        $dao->setReturnValue('searchTrackerHierarchy', $dar, array(array(111, 112, 113, 114)));
        return $dao;
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
        return new Tracker_HierarchyFactory($dao, mock('TrackerFactory'), mock('Tracker_ArtifactFactory'));
    }
}

class Tracker_HierarchyFactoryGetParentTest extends TuleapTestCase {
    private $dao;
    private $hierarchy_factory;
    private $user;
    private $artifact;
    private $artifact_id;
    private $artifact_factory;

    public function setUp() {
        parent::setUp();

        $this->artifact_id = 123;
        $this->artifact    = aMockArtifact()->withId($this->artifact_id)->build();

        $this->dao               = mock('Tracker_Hierarchy_Dao');
        $this->artifact_factory  = mock('Tracker_ArtifactFactory');
        $this->hierarchy_factory = new Tracker_HierarchyFactory($this->dao, mock('TrackerFactory'), $this->artifact_factory);

        $this->user    = aUser()->build();
    }

    public function itReturnsTheParent() {
        $artifact_id  = 345;
        $artifact_row = array('id' => "$artifact_id");
        stub($this->artifact_factory)->getInstanceFromRow($artifact_row)->returns(aMockArtifact()->withId($artifact_id)->build());
        stub($this->dao)->getParentsInHierarchy($this->artifact_id)->returnsDar($artifact_row);

        $parent = $this->hierarchy_factory->getParentArtifact($this->user, $this->artifact);
        $this->assertEqual($parent->getId(), $artifact_id);
    }

    public function itReturnsNullWhenNoParents() {
        stub($this->dao)->getParentsInHierarchy()->returnsEmptyDar();

        $parent = $this->hierarchy_factory->getParentArtifact($this->user, $this->artifact);
        $this->assertEqual($parent, null);
    }

    public function itReturnsNullWhenDatabaseReturnsCrap() {
        stub($this->dao)->getParentsInHierarchy()->returns(false);

        $parent = $this->hierarchy_factory->getParentArtifact($this->user, $this->artifact);
        $this->assertEqual($parent, null);
    }

    public function itReturnsNullWhenDatabaseReturnsError() {
        stub($this->dao)->getParentsInHierarchy()->returnsDarWithErrors();

        $parent = $this->hierarchy_factory->getParentArtifact($this->user, $this->artifact);
        $this->assertEqual($parent, null);
    }

    public function itThrowAnExceptionWhen2Parents() {
        $artifact_345_row = array('id' => '345');
        $artifact_346_row = array('id' => '346');
        stub($this->dao)->getParentsInHierarchy()->returnsDar($artifact_345_row, $artifact_346_row);

        $this->artifact_factory->setReturnValueAt(0, 'getInstanceFromRow', aMockArtifact()->withId(345)->build());
        $this->artifact_factory->setReturnValueAt(1, 'getInstanceFromRow', aMockArtifact()->withId(346)->build());


        $this->expectException('Tracker_Hierarchy_MoreThanOneParentException');

        $this->hierarchy_factory->getParentArtifact($this->user, $this->artifact);
    }
}

class Tracker_HierarchyFactoryGetAllAncestorsTest extends TuleapTestCase {
    private $hierarchy_factory;
    private $user;
    private $sprint;

    public function setUp() {
        parent::setUp();
        $this->user              = aUser()->build();
        $this->hierarchy_factory = partial_mock('Tracker_HierarchyFactory', array('getParentArtifact'));
        $this->sprint            = anArtifact()->withId(1)->build();
    }

    public function itReturnsEmptyArrayWhenNoAncestors() {
        stub($this->hierarchy_factory)->getParentArtifact($this->user, $this->sprint)->returns(null);

        $this->assertEqual($this->hierarchy_factory->getAllAncestors($this->user, $this->sprint), array());
    }

    public function itReturnsTheParentWhenThereIsOnlyOne() {
        $release = anArtifact()->build();

        $this->hierarchy_factory->setReturnValueAt(0, 'getParentArtifact', $release, array($this->user, $this->sprint));

        $this->assertEqual($this->hierarchy_factory->getAllAncestors($this->user, $this->sprint), array($release));
    }

    public function itReturnsNothingWhenChildReferenceItselfAsParent() {
        stub($this->hierarchy_factory)->getParentArtifact($this->user, $this->sprint)->returns($this->sprint);

        $this->assertEqual($this->hierarchy_factory->getAllAncestors($this->user, $this->sprint), array());
    }

    public function itReturnsParentsOnlyOnceWhenTheParentReferenceItself() {
        $release = anArtifact()->withId(3)->build();

        $this->hierarchy_factory->setReturnValueAt(0, 'getParentArtifact', $release, array($this->user, $this->sprint));
        // simulate loop on release (release reference itself)
        $this->hierarchy_factory->setReturnValueAt(1, 'getParentArtifact', $release, array($this->user, $release));
        $this->hierarchy_factory->setReturnValueAt(2, 'getParentArtifact', $release, array($this->user, $release));
        //...

        $this->assertEqual($this->hierarchy_factory->getAllAncestors($this->user, $this->sprint), array($release));
    }

    public function itReturnsParentsOnlyOnceWhenThereIsACycleBetweenParents() {
        $product = anArtifact()->withId(2)->build();
        $release = anArtifact()->withId(3)->build();

        $this->hierarchy_factory->setReturnValueAt(0, 'getParentArtifact', $release, array($this->user, $this->sprint));
        $this->hierarchy_factory->setReturnValueAt(1, 'getParentArtifact', $product, array($this->user, $release));
        $this->hierarchy_factory->setReturnValueAt(1, 'getParentArtifact', $release, array($this->user, $product));

        $this->assertEqual($this->hierarchy_factory->getAllAncestors($this->user, $this->sprint), array($release, $product));
    }

    public function itReturnsParentsOnlyOnceWhenThereIsAFullCycle() {
        $product = anArtifact()->withId(2)->build();
        $release = anArtifact()->withId(3)->build();

        $this->hierarchy_factory->setReturnValueAt(0, 'getParentArtifact', $release, array($this->user, $this->sprint));
        $this->hierarchy_factory->setReturnValueAt(1, 'getParentArtifact', $product, array($this->user, $release));
        $this->hierarchy_factory->setReturnValueAt(1, 'getParentArtifact', $this->sprint, array($this->user, $product));

        $this->assertEqual($this->hierarchy_factory->getAllAncestors($this->user, $this->sprint), array($release, $product));
    }

    public function itReturnsSeveralParents() {
        $product = anArtifact()->withId(2)->build();
        $release = anArtifact()->withId(3)->build();

        $this->hierarchy_factory->setReturnValueAt(0, 'getParentArtifact', $release, array($this->user, $this->sprint));
        $this->hierarchy_factory->setReturnValueAt(1, 'getParentArtifact', $product, array($this->user, $release));

        $this->assertEqual($this->hierarchy_factory->getAllAncestors($this->user, $this->sprint), array($release, $product));
    }
}

class Tracker_HierarchyFactoryGetSiblingsTest extends TuleapTestCase {
    private $hierarchy_factory;
    private $user;
    private $sprint_1;
    private $sprint_2;
    private $release_1;

    public function setUp() {
        parent::setUp();
        $this->user              = aUser()->build();
        $this->hierarchy_factory = partial_mock('Tracker_HierarchyFactory', array('getParentArtifact'));
        $this->sprint_1          = anArtifact()->withId(1)->build();
        $this->sprint_2          = anArtifact()->withId(2)->build();
        $this->release_1         = aMockArtifact()->withId(101)->build();
    }

    public function itReturnsEmptyArrayWhenNoParent() {
        stub($this->hierarchy_factory)->getParentArtifact($this->user, $this->sprint_1)->returns(null);
        
        $this->assertEqual(array(), $this->hierarchy_factory->getSiblings($this->user, $this->sprint_1));
    }
    
    public function itReturnsTheGivenArtifactWhenParentHasNoOtherChildren() {
        stub($this->release_1)->getHierarchyLinkedArtifacts($this->user)->returns(array($this->sprint_1));
        stub($this->hierarchy_factory)->getParentArtifact($this->user, $this->sprint_1)->returns($this->release_1);
        $this->assertEqual(array($this->sprint_1), $this->hierarchy_factory->getSiblings($this->user, $this->sprint_1));
    }
    
    public function itReturnsTheGivenArtifactAndTheBroWhenThereIsOneBro() {
        stub($this->release_1)->getHierarchyLinkedArtifacts($this->user)->returns(array($this->sprint_1, $this->sprint_2));
        stub($this->hierarchy_factory)->getParentArtifact($this->user, $this->sprint_1)->returns($this->release_1);
        $this->assertEqual(array($this->sprint_1, $this->sprint_2), $this->hierarchy_factory->getSiblings($this->user, $this->sprint_1));
    }
}
?>
