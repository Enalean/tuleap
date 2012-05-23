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
        $artifact2         = mock('Tracker_Artifact');
        stub($artifact2)->getLinkedArtifacts()->returns(array());

        $artifact3         = mock('Tracker_Artifact');
        stub($artifact3)->getLinkedArtifacts()->returns(array());

        $artifact1         = mock('Tracker_Artifact');
        stub($artifact1)->getLinkedArtifacts()->returns(array($artifact2, $artifact3));

        $expected_list     = array($artifact1, $artifact2);
        $field             = mock('Tracker_FormElement_Field_ArtifactLink');
        stub($field)->getLinkedArtifacts($this->changeset, $this->user)->returns($expected_list);
        stub($field)->userCanRead($this->user)->returns(true);

        stub($this->factory)->getUsedArtifactLinkFields()->returns(array($field));

        $expected_result = array($artifact1);
        $this->assertEqual($expected_result, $this->artifact->getUniqueLinkedArtifacts($this->user));
    }

    /**
     * Artifact Links
     * - art 1
     *     - art 2
     *     - art 3
     * - art 4
     *     - art 2 (should be hidden)
     */
     public function itReturnsOnlyOneIfTwoLinksIdenticalISubHierarchies() {

     }
}
?>