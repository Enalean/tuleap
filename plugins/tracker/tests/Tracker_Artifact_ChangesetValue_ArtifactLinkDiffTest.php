<?php
/**
 * Copyright Enalean (c) 2014. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

require_once 'bootstrap.php';

class Tracker_Artifact_ChangesetValue_ArtifactLinkDiffTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $this->user                = mock('PFUser');
        $this->artifact_link_info1 = stub('Tracker_ArtifactLinkInfo')->userCanView($this->user)->returns(true);
        $this->artifact_link_info2 = stub('Tracker_ArtifactLinkInfo')->userCanView($this->user)->returns(true);
        $this->artifact_link_info3 = stub('Tracker_ArtifactLinkInfo')->userCanView($this->user)->returns(false);
        $this->artifact_link_info4 = stub('Tracker_ArtifactLinkInfo')->userCanView($this->user)->returns(false);
        $this->artifact_link_info5 = stub('Tracker_ArtifactLinkInfo')->userCanView($this->user)->returns(false);
        $this->artifact_link_info6 = stub('Tracker_ArtifactLinkInfo')->userCanView($this->user)->returns(true);

        $this->previous = array(
            1 => $this->artifact_link_info1,
            2 => $this->artifact_link_info2,
            3 => $this->artifact_link_info3
        );

        $this->next = array(
            4 => $this->artifact_link_info4,
            5 => $this->artifact_link_info5,
            6 => $this->artifact_link_info6
        );

        $this->artifact_link_diff = new Tracker_Artifact_ChangesetValue_ArtifactLinkDiff($this->previous, $this->next);
    }

    public function itReturnsOnlyAddedLinksUserCanSee() {
        $expected = array(
            $this->artifact_link_info6
        );

        $this->assertEqual($this->artifact_link_diff->getAddedUserCanSee($this->user), $expected);
    }

    public function itReturnsOnlyRemovedLinksUserCanSee() {
        $expected = array(
            $this->artifact_link_info1,
            $this->artifact_link_info2
        );

        $this->assertEqual($this->artifact_link_diff->getRemovedUserCanSee($this->user), $expected);
    }

    public function itReturnsAllAddedLinks() {
        $expected = array(
            $this->artifact_link_info4,
            $this->artifact_link_info5,
            $this->artifact_link_info6
        );

        $this->assertEqual($this->artifact_link_diff->getAdded($this->user), $expected);
    }

    public function itReturnsAllRemovedLinks() {
        $expected = array(
            $this->artifact_link_info1,
            $this->artifact_link_info2,
            $this->artifact_link_info3
        );

        $this->assertEqual($this->artifact_link_diff->getRemoved($this->user), $expected);
    }

}
