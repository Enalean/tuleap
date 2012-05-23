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

    public function itReturnsAnEmptyListWhenThereIsNoArtifactLinkField() {
        $tracker  = aTracker()->withId('101')->build();
        $user     = aUser()->build();

        $factory  = stub('Tracker_FormElementFactory')->getUsedArtifactLinkFields($tracker)->returns(array());

        $artifact = anArtifact()
            ->withTracker($tracker)
            ->withFormElementFactory($factory)
            ->build();

        $links = $artifact->getLinkedArtifacts($user);
        $this->assertEqual(array(), $links);
    }

    public function itReturnsAlistOfTheLinkedArtifacts() {
        $user          = aUser()->build();
        $expected_list = array(
            new Tracker_Artifact(111, null, null, null, null),
            new Tracker_Artifact(222, null, null, null, null)
        );

        $changeset = new MockTracker_Artifact_Changeset();

        $field = mock('Tracker_FormElement_Field_ArtifactLink');
        stub($field)->getLinkedArtifacts($changeset, $user)->returns($expected_list);
        stub($field)->userCanRead($user)->returns(true);

        $tracker  = aTracker()->build();

        $factory  = stub('Tracker_FormElementFactory')->getUsedArtifactLinkFields($tracker)->returns(array($field));

        $artifact = anArtifact()
            ->withTracker($tracker)
            ->withFormElementFactory($factory)
            ->withChangesets(array($changeset))
            ->build();

        $this->assertEqual($expected_list, $artifact->getLinkedArtifacts($user));
    }

    public function itReturnsEmptyArrayIfUserCannotSeeArtifactLinkField() {
        $current_user = aUser()->build();

        $field        = new MockTracker_FormElement_Field_ArtifactLink();
        stub($field)->userCanRead($current_user)->returns(false);

        $tracker      = aTracker()->build();
        $factory      = stub('Tracker_FormElementFactory')->getUsedArtifactLinkFields($tracker)->returns(array($field));
        $artifact     = anArtifact()
            ->withTracker($tracker)
            ->withFormElementFactory($factory)
            ->build();

        $this->assertEqual($artifact->getAnArtifactLinkField($current_user), null);
    }

    /**
     * Artifact Links
     * - art 1
     *   - art 2
     *   - art 3
     * - art 2 (should be hidden)
     */
    public function itReturnsOnlyOneIfTwoLinksIdentical() {
        $user          = aUser()->build();
        $changeset = new MockTracker_Artifact_Changeset();

        $child_tracker  = aTracker()->withId(33)->build();
        $non_child_tracker = aTracker()->withId(44)->build();

        $factory = new MockTracker_FormElementFactory();

        $parent_tracker = aTracker()->withId(22)->build();
        $child_tracker  = aTracker()->withId(33)->build();
        $non_child_tracker = aTracker()->withId(44)->build();

        $artifact = anArtifact()
            ->withTracker($parent_tracker)
            ->withFormElementFactory($factory)
            ->withChangesets(array($changeset))
            ->build();

        $artifact2 = mock('Tracker_Artifact');
        stub($artifact2)->getLinkedArtifacts()->returns(array());

        $artifact3 = mock('Tracker_Artifact');
        stub($artifact3)->getLinkedArtifacts()->returns(array());

        $artifact1 = mock('Tracker_Artifact');
        stub($artifact1)->getLinkedArtifacts()->returns(array($artifact2, $artifact3));

        $expected_list = array($artifact1, $artifact2);

        $field = mock('Tracker_FormElement_Field_ArtifactLink');
        stub($field)->getLinkedArtifacts($changeset, $user)->returns($expected_list);
        stub($field)->userCanRead($user)->returns(true);
        $factory->setReturnValue('getUsedArtifactLinkFields', array($field));
        //$artifactLinks = $artifactLinkToBeKept->getLinkedArtifacts($user);

        $expected_result = array($artifact1);
        $this->assertEqual($expected_result, $artifact->getUniqueLinkedArtifacts($user));
    }
}


?>