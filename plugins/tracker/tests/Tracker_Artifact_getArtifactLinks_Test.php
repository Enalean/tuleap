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
require_once('bootstrap.php');

class Tracker_Artifact_getArtifactLinks_Test extends TuleapTestCase {

    private $current_id = 100;
    private $user;
    private $tracker;
    private $factory;
    private $changeset;
    private $artifact;

    public function setUp() {
        parent::setUp();

        $this->user      = aUser()->build();
        $this->tracker   = aTracker()->withId($this->current_id)->build();
        $this->factory   = mock('Tracker_FormElementFactory');
        $this->changeset = mock('Tracker_Artifact_Changeset');
        $this->artifact  = anArtifact()
            ->withId($this->current_id + 100)
            ->withTracker($this->tracker)
            ->withFormElementFactory($this->factory)
            ->withChangesets(array($this->changeset))
            ->build()
        ;
        $hierarchy_factory = mock('Tracker_HierarchyFactory');
        stub($hierarchy_factory)->getChildren()->returns(array());
        $this->artifact->setHierarchyFactory($hierarchy_factory);
    }

    public function tearDown() {
        parent::tearDown();
        $this->current_id ++;
    }

    public function itReturnsAnEmptyListWhenThereIsNoArtifactLinkField() {
        stub($this->factory)->getUsedArtifactLinkFields($this->tracker)->returns(array());
        $links = $this->artifact->getLinkedArtifacts($this->user);
        $this->assertEqual(array(), $links);
    }

    public function itReturnsAlistOfTheLinkedArtifacts() {
        $expected_list = array(
            new Tracker_Artifact(111, null, 0, null, null, null),
            new Tracker_Artifact(222, null, 0, null, null, null)
        );

        $field = mock('Tracker_FormElement_Field_ArtifactLink');
        stub($field)->getLinkedArtifacts($this->changeset, $this->user)->returns($expected_list);

        stub($this->factory)->getAnArtifactLinkField($this->user, $this->tracker)->returns($field);

        $this->assertEqual($expected_list, $this->artifact->getLinkedArtifacts($this->user));
    }

    /**
     * Artifact Links
     * - art 1
     *   - art 2
     *   - art 3
     * - art 2 (should be hidden)
     */
    public function itReturnsOnlyOneIfTwoLinksIdentical() {
        $artifact3 = $this->giveMeAnArtifactWithChildren();
        $artifact2 = $this->giveMeAnArtifactWithChildren();
        $artifact1 = $this->giveMeAnArtifactWithChildren($artifact2, $artifact3);

        $field     = mock('Tracker_FormElement_Field_ArtifactLink');
        stub($field)->getLinkedArtifacts($this->changeset, $this->user)->returns(array($artifact1, $artifact2));

        stub($this->factory)->getAnArtifactLinkField($this->user, $this->tracker)->returns($field);

        $expected_result = array($artifact1);
        $this->assertEqual($expected_result, $this->artifact->getUniqueLinkedArtifacts($this->user));
    }

    /**
     * Artifact Links
     * - art 1
     *     - art 2
     *     - art 3
     *         -art 4
     * - art 4 (should be hidden)
     */
    public function itReturnsOnlyOneIfTwoLinksIdenticalInSubHierarchies() {
        $artifact4 = $this->giveMeAnArtifactWithChildren();
        $artifact3 = $this->giveMeAnArtifactWithChildren($artifact4);
        $artifact2 = $this->giveMeAnArtifactWithChildren();
        $artifact1 = $this->giveMeAnArtifactWithChildren($artifact2, $artifact3);
    
        $field     = mock('Tracker_FormElement_Field_ArtifactLink');
        stub($field)->getLinkedArtifacts($this->changeset, $this->user)->returns(array($artifact1, $artifact4));
        stub($this->factory)->getAnArtifactLinkField($this->user, $this->tracker)->returns($field);
    
        $expected_result = array($artifact1);
        $this->assertEqual($expected_result, $this->artifact->getUniqueLinkedArtifacts($this->user));
    }
     
    /**
     * Artifact Links
     * └ art 0 (Sprint)
     *   ┝ art 1 (US)
     *   │ └ art 2 (Task)
     *   │   ┝ art 3 (Bug)
     *   │   └ art 4 (Bug)
     *   └ art 3
     *
     * Tracker hierarchy:
     * - US
     *   - Task
     * - Bug
     * - Sprint
     *
     * As Bug is not a child of Task, we should not get art 3 and 4 under task 2
     * However as art 3 is linked to art 0 we should get it under art 0
     */
    public function itDoesNotReturnArtifactsThatAreNotInTheHierarchy() {
        $us_tracker     = stub('Tracker')->getId()->returns(101);
        $task_tracker   = stub('Tracker')->getId()->returns(102);
        $bug_tracker    = stub('Tracker')->getId()->returns(103);
        $sprint_tracker = stub('Tracker')->getId()->returns(104);
        
        $hierarchy_factory = mock('Tracker_HierarchyFactory');
        stub($hierarchy_factory)->getChildren($us_tracker->getId())->returns(array($task_tracker));
        stub($hierarchy_factory)->getChildren($task_tracker->getId())->returns(array());
        stub($hierarchy_factory)->getChildren($bug_tracker->getId())->returns(array());
        stub($hierarchy_factory)->getChildren($sprint_tracker->getId())->returns(array());
        
        $artifact0 = TestHelper::getPartialMock('Tracker_Artifact', array('getAnArtifactLinkField', 'getLastChangeset'));
        $artifact1 = TestHelper::getPartialMock('Tracker_Artifact', array('getAnArtifactLinkField', 'getLastChangeset'));
        $artifact2 = TestHelper::getPartialMock('Tracker_Artifact', array('getAnArtifactLinkField', 'getLastChangeset'));
        $artifact3 = TestHelper::getPartialMock('Tracker_Artifact', array('getAnArtifactLinkField', 'getLastChangeset'));
        $artifact4 = TestHelper::getPartialMock('Tracker_Artifact', array('getAnArtifactLinkField', 'getLastChangeset'));
        
        $artifact0->setHierarchyFactory($hierarchy_factory);
        $artifact1->setHierarchyFactory($hierarchy_factory);
        $artifact2->setHierarchyFactory($hierarchy_factory);
        $artifact3->setHierarchyFactory($hierarchy_factory);
        $artifact4->setHierarchyFactory($hierarchy_factory);

        $artifact0->setId(0);
        $artifact1->setId(1);
        $artifact2->setId(2);
        $artifact3->setId(3);
        $artifact4->setId(4);
        
        $artifact0->setTracker($sprint_tracker);
        $artifact1->setTracker($us_tracker);
        $artifact2->setTracker($task_tracker);
        $artifact3->setTracker($bug_tracker);
        $artifact4->setTracker($bug_tracker);
        
        $this->setArtifactChildren($artifact0, array($artifact1, $artifact3));
        $this->setArtifactChildren($artifact1, array($artifact2));
        $this->setArtifactChildren($artifact2, array($artifact2, $artifact4));
        
        $expected_result = array($artifact1, $artifact3);
        $this->assertEqual($expected_result, $artifact0->getUniqueLinkedArtifacts($this->user));
    }
     
    private function setArtifactChildren($artifact, $children) {
        $alfield = stub('Tracker_FormElement_Field_ArtifactLink')
            ->getLinkedArtifacts()
            ->returns($children);
        stub($artifact)->getAnArtifactLinkField()->returns($alfield);
    }
     
    /**
     *
     * @param $child1 optional artifact link field child
     * @param $child2 optional artifact link field child ...
     *
     * @return Tracker_Artifact
     */
    public function giveMeAnArtifactWithChildren() {
        $children  = func_get_args();
        $sub_trackers = array();
        foreach ($children as $child) {
            $sub_trackers[] = $child->getTracker();
        }
        
        $this->current_id++;
        $tracker   = aTracker()->withId($this->current_id)->build();
        
        $hierarchy_factory = mock('Tracker_HierarchyFactory');
        stub($hierarchy_factory)->getChildren($tracker->getId())->returns($sub_trackers);
        
        $changeset = mock('Tracker_Artifact_Changeset');
        $field     = mock('Tracker_FormElement_Field_ArtifactLink');
        stub($field)->getLinkedArtifacts($changeset, $this->user)->returns($children);
        stub($this->factory)->getAnArtifactLinkField($this->user, $tracker)->returns($field);
    
        $artifact_id = $this->current_id + 100;
        $this->artifact_collaborators[$artifact_id] = array(
            'field'     => $field,
            'changeset' => $changeset,
        );
        
        return anArtifact()
            ->withId($artifact_id)
            ->withTracker($tracker)
            ->withFormElementFactory($this->factory)
            ->withChangesets(array($changeset))
            ->withHierarchyFactory($hierarchy_factory)
            ->build()
        ;
    }
}
?>
