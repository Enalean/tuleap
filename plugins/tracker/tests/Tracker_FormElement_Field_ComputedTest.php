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

class Tracker_FormElement_Field_ComputedTest extends TuleapTestCase {
    private $user;
    private $field;
    private $formelement_factory;

    public function setUp() {
        parent::setUp();
        $this->user  = mock('PFUser');
        $this->field = TestHelper::getPartialMock('Tracker_FormElement_Field_Computed', array('getProperty'));
        stub($this->field)->getProperty()->returns('effort');

        $this->formelement_factory = mock('Tracker_FormElementFactory');
        Tracker_FormElementFactory::setInstance($this->formelement_factory);
    }

    public function tearDown() {
        parent::tearDown();
        Tracker_FormElementFactory::clearInstance();
    }

    public function itComputesDirectValues() {
        $sub_artifact1 = anArtifact()->withId(1)->withTracker(aTracker()->build())->build();
        $sub_artifact2 = anArtifact()->withId(2)->withTracker(aTracker()->build())->build();
        $artifact      = stub('Tracker_Artifact')->getLinkedArtifacts()->returns(array($sub_artifact1, $sub_artifact2));

        $field = mock('Tracker_FormElement_Field_Float');
        stub($field)->getComputedValue($this->user, $sub_artifact1, null, '*')->returns(5);
        stub($field)->getComputedValue($this->user, $sub_artifact2, null, '*')->returns(15);

        stub($this->formelement_factory)->getComputableFieldByNameForUser()->returns($field);

        $this->assertEqual(20, $this->field->getComputedValue($this->user, $artifact));
    }

    public function itReturnsNullWhenThereAreNoData() {
        $sub_artifact1 = anArtifact()->withId(1)->withTracker(aTracker()->build())->build();
        $sub_artifact2 = anArtifact()->withId(2)->withTracker(aTracker()->build())->build();
        $artifact      = stub('Tracker_Artifact')->getLinkedArtifacts()->returns(array($sub_artifact1, $sub_artifact2));

        $field = mock('Tracker_FormElement_Field_Float');
        stub($field)->getComputedValue()->returns(null);

        stub($this->formelement_factory)->getComputableFieldByNameForUser()->returns($field);

        $this->assertIdentical(null, $this->field->getComputedValue($this->user, $artifact));
    }

    public function itIgnoreCyclesInChildrens() {
        $sub_artifact1 = anArtifact()->withId(1)->withTracker(aTracker()->withId(150)->build())->build();
        $artifact      = mock('Tracker_Artifact');
        stub($artifact)->getTracker()->returns(aTracker()->withId(300)->build());
        stub($artifact)->getLinkedArtifacts()->returns(array($artifact, $sub_artifact1));

        $field = mock('Tracker_FormElement_Field_Float');
        stub($field)->getComputedValue($this->user, $sub_artifact1, null, '*')->returns(5);

        stub($this->formelement_factory)->getComputableFieldByNameForUser(150, 'effort', $this->user)->returns($field);
        stub($this->formelement_factory)->getComputableFieldByNameForUser(300, 'effort', $this->user)->returns($this->field);

        $this->assertEqual(5, $this->field->getComputedValue($this->user, $artifact));
    }

    public function itComputesRemainingEffortAtAGivenTime() {
        $timestamp = mktime(23, 59, 59, 7, 3, 2012);

        $sub_artifact1 = anArtifact()->withId(1)->withTracker(aTracker()->build())->build();
        $sub_artifact2 = anArtifact()->withId(2)->withTracker(aTracker()->build())->build();
        $artifact      = stub('Tracker_Artifact')->getLinkedArtifacts()->returns(array($sub_artifact1, $sub_artifact2));

        $field = mock('Tracker_FormElement_Field_Float');
        stub($field)->getComputedValue($this->user, $sub_artifact1, $timestamp, '*')->returns(5);
        stub($field)->getComputedValue($this->user, $sub_artifact2, $timestamp, '*')->returns(15);

        stub($this->formelement_factory)->getComputableFieldByNameForUser()->returns($field);

        $this->assertEqual(20, $this->field->getComputedValue($this->user, $artifact, $timestamp));
    }
}

?>
