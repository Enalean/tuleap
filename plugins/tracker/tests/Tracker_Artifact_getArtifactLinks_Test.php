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

require_once(dirname(__FILE__).'/../include/constants.php');
require_once(dirname(__FILE__).'/../include/Tracker/Artifact/Tracker_Artifact.class.php');

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
            new Tracker_Artifact(111, null, null, null, null),
            new Tracker_Artifact(222, null, null, null, null)
        );

        $field = mock('Tracker_FormElement_Field_ArtifactLink');
        stub($field)->getLinkedArtifacts($this->changeset, $this->user)->returns($expected_list);
        stub($field)->userCanRead($this->user)->returns(true);

        stub($this->factory)->getUsedArtifactLinkFields($this->tracker)->returns(array($field));

        $this->assertEqual($expected_list, $this->artifact->getLinkedArtifacts($this->user));
    }

    public function itReturnsEmptyArrayIfUserCannotSeeArtifactLinkField() {
        $field = new MockTracker_FormElement_Field_ArtifactLink();
        stub($field)->userCanRead($this->user)->returns(false);
        stub($this->factory)->getUsedArtifactLinkFields($this->tracker)->returns(array($field));

        $this->assertEqual($this->artifact->getAnArtifactLinkField($this->user), null);
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
        stub($field)->userCanRead($this->user)->returns(true);

        stub($this->factory)->getUsedArtifactLinkFields($this->tracker)->returns(array($field));

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
         stub($field)->userCanRead($this->user)->returns(true);
         stub($this->factory)->getUsedArtifactLinkFields($this->tracker)->returns(array($field));

         $expected_result = array($artifact1);
         $this->assertEqual($expected_result, $this->artifact->getUniqueLinkedArtifacts($this->user));
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
         $this->current_id++;
         $tracker   = aTracker()->withId($this->current_id)->build();
         $changeset = mock('Tracker_Artifact_Changeset');
         $field     = mock('Tracker_FormElement_Field_ArtifactLink');
         stub($field)->getLinkedArtifacts($changeset, $this->user)->returns($children);
         stub($field)->userCanRead($this->user)->returns(true);
         stub($this->factory)->getUsedArtifactLinkFields($tracker)->returns(array($field));

         return anArtifact()
             ->withId($this->current_id + 100)
             ->withTracker($tracker)
             ->withFormElementFactory($this->factory)
             ->withChangesets(array($changeset))
             ->build()
         ;
     }
}
?>
