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
    private $dao;
    private $artifact_factory;

    public function setUp() {
        parent::setUp();
        $this->user  = mock('PFUser');
        $this->dao   = mock('Tracker_FormElement_Field_ComputedDao');
        $this->field = TestHelper::getPartialMock('Tracker_FormElement_Field_Computed', array('getProperty', 'getDao'));
        stub($this->field)->getProperty('target_field_name')->returns('effort');
        stub($this->field)->getProperty('fast_compute')->returns(0);
        stub($this->field)->getDao()->returns($this->dao);

        $this->artifact_factory = mock('Tracker_ArtifactFactory');
        Tracker_ArtifactFactory::setInstance($this->artifact_factory);

        $this->formelement_factory = mock('Tracker_FormElementFactory');
        Tracker_FormElementFactory::setInstance($this->formelement_factory);
    }

    public function tearDown() {
        parent::tearDown();
        Tracker_FormElementFactory::clearInstance();
        Tracker_ArtifactFactory::clearInstance();
    }

    public function itComputesDirectValues() {
        stub($this->dao)->getFieldValues(array(233), 'effort')->returnsDar(
            array('id' => 750, 'type' => 'int', 'int_value' => 5),
            array('id' => 751, 'type' => 'int', 'int_value' => 15)
        );

        $child_art = stub('Tracker_Artifact')->userCanView()->returns(true);
        stub($this->artifact_factory)->getInstanceFromRow()->returns($child_art);

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $this->assertEqual(20, $this->field->getComputedValue($this->user, $artifact));
    }

    public function itReturnsNullWhenThereAreNoDataBecauseNoDataMeansNoPlotOnChart() {
        stub($this->dao)->getFieldValues(array(233), 'effort')->returnsEmptyDar();

        $child_art = stub('Tracker_Artifact')->userCanView()->returns(true);
        stub($this->artifact_factory)->getInstanceFromRow()->returns($child_art);

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $this->assertIdentical(null, $this->field->getComputedValue($this->user, $artifact));
    }
}

class Tracker_FormElement_Field_Computed_DoNoCountTwiceTest extends TuleapTestCase {
    private $user;
    private $field;
    private $formelement_factory;
    private $dao;
    private $artifact_factory;
    private $artifact;

    public function setUp() {
        parent::setUp();
        $this->user  = mock('PFUser');
        $this->dao   = mock('Tracker_FormElement_Field_ComputedDao');
        $this->field = $this->getComputedField();

        $this->artifact_factory = mock('Tracker_ArtifactFactory');
        Tracker_ArtifactFactory::setInstance($this->artifact_factory);

        $this->formelement_factory = mock('Tracker_FormElementFactory');
        Tracker_FormElementFactory::setInstance($this->formelement_factory);

        $this->artifact = stub('Tracker_Artifact')->getId()->returns(233);
        stub($this->artifact)->userCanView()->returns(true);
        stub($this->artifact)->getTracker()->returns(aMockTracker()->withId(12)->build());
    }

    private function getComputedField() {
        $field = partial_mock('Tracker_FormElement_Field_Computed', array('getProperty', 'getDao'));
        stub($field)->getProperty('target_field_name')->returns('effort');
        stub($field)->getProperty('fast_compute')->returns(0);
        stub($field)->getDao()->returns($this->dao);
        return $field;
    }

    public function tearDown() {
        parent::tearDown();
        Tracker_FormElementFactory::clearInstance();
        Tracker_ArtifactFactory::clearInstance();
    }

    public function itComputesRecursively() {
        $row_750 = array('id' => 750, 'type' => 'int', 'int_value' => 5);
        $row_751 = array('id' => 751, 'type' => 'int', 'int_value' => 15);
        $row_766 = array('id' => 766, 'type' => 'computed');
        $row_722 = array('id' => 722, 'type' => 'int', 'int_value' => 5);

        stub($this->dao)->getFieldValues(array(233), 'effort')->returnsDar(
            $row_750,
            $row_751,
            $row_766
        );
        stub($this->dao)->getFieldValues(array(766), 'effort')->returnsDar(
            $row_722
        );

        stub($this->artifact_factory)->getInstanceFromRow($row_750)->returns($this->getArtifact(750));
        stub($this->artifact_factory)->getInstanceFromRow($row_751)->returns($this->getArtifact(751));
        stub($this->artifact_factory)->getInstanceFromRow($row_722)->returns($this->getArtifact(722));
        stub($this->artifact_factory)->getInstanceFromRow($row_766)->returns($this->getArtifact(766));

        stub($this->formelement_factory)->getComputableFieldByNameForUser()->returns($this->getComputedField());

        $this->assertEqual(25, $this->field->getComputedValue($this->user, $this->artifact));
    }

    public function itDoesntMakeLoopInGraph() {
        $row_750 = array('id' => 750, 'type' => 'int', 'int_value' => 5);
        $row_751 = array('id' => 751, 'type' => 'int', 'int_value' => 15);
        $row_752 = array('id' => 752, 'type' => 'int', 'int_value' => 10);
        $row_753 = array('id' => 753, 'type' => 'int', 'int_value' => 10);
        $row_766 = array('id' => 766, 'type' => 'computed');
        $row_777 = array('id' => 777, 'type' => 'computed');
        $row_233 = array('id' => 233, 'type' => 'computed');

        stub($this->dao)->getFieldValues(array(233), 'effort')->returnsDar(
            $row_750,
            $row_751,
            $row_766,
            $row_777
        );
        stub($this->dao)->getFieldValues(array(766), 'effort')->returnsDar(
            $row_752,
            $row_233
        );
        stub($this->dao)->getFieldValues(array(777), 'effort')->returnsDar(
            $row_753,
            $row_766
        );

        stub($this->artifact_factory)->getInstanceFromRow($row_750)->returns($this->getArtifact(750));
        stub($this->artifact_factory)->getInstanceFromRow($row_751)->returns($this->getArtifact(751));
        stub($this->artifact_factory)->getInstanceFromRow($row_752)->returns($this->getArtifact(752));
        stub($this->artifact_factory)->getInstanceFromRow($row_753)->returns($this->getArtifact(753));
        stub($this->artifact_factory)->getInstanceFromRow($row_766)->returns($this->getArtifact(766));
        stub($this->artifact_factory)->getInstanceFromRow($row_777)->returns($this->getArtifact(777));
        stub($this->artifact_factory)->getInstanceFromRow($row_233)->returns($this->artifact);

        stub($this->formelement_factory)->getComputableFieldByNameForUser()->returns($this->getComputedField());

        $this->assertEqual(40, $this->field->getComputedValue($this->user, $this->artifact));
    }

    public function itDoesntCountTwiceTheFinalData() {
        $row_750 = array('id' => 750, 'type' => 'int', 'int_value' => 5);
        $row_751 = array('id' => 751, 'type' => 'int', 'int_value' => 15);
        $row_766 = array('id' => 766, 'type' => 'computed');
        $row_777 = array('id' => 777, 'type' => 'computed');

        stub($this->dao)->getFieldValues(array(233), 'effort')->returnsDar(
            $row_750,
            $row_751,
            $row_766,
            $row_777
        );
        stub($this->dao)->getFieldValues(array(766), 'effort')->returnsDar(
            $row_750
        );
        stub($this->dao)->getFieldValues(array(777), 'effort')->returnsDar(
            $row_751
        );

        stub($this->artifact_factory)->getInstanceFromRow($row_750)->returns($this->getArtifact(750));
        stub($this->artifact_factory)->getInstanceFromRow($row_751)->returns($this->getArtifact(751));
        stub($this->artifact_factory)->getInstanceFromRow($row_766)->returns($this->getArtifact(766));
        stub($this->artifact_factory)->getInstanceFromRow($row_777)->returns($this->getArtifact(777));

        stub($this->formelement_factory)->getComputableFieldByNameForUser()->returns($this->getComputedField());

        $this->assertEqual(20, $this->field->getComputedValue($this->user, $this->artifact));
    }

    private function getArtifact($id) {
        $artifact = stub('Tracker_Artifact')->userCanView()->returns(true);
        stub($artifact)->getTracker()->returns(aMockTracker()->withId(12)->build());
        stub($artifact)->getId()->returns($id);
        return $artifact;
    }
}

class Tracker_FormElement_Field_Compute_FastComputeTest extends TuleapTestCase {
    private $user;
    private $field;
    private $formelement_factory;
    private $dao;
    private $artifact_factory;

    public function setUp() {
        parent::setUp();
        $this->user  = mock('PFUser');
        $this->dao   = mock('Tracker_FormElement_Field_ComputedDao');
        $this->field = TestHelper::getPartialMock('Tracker_FormElement_Field_Computed', array('getProperty', 'getDao'));
        stub($this->field)->getProperty('target_field_name')->returns('effort');
        stub($this->field)->getProperty('fast_compute')->returns(1);
        stub($this->field)->getDao()->returns($this->dao);

        $this->artifact_factory = mock('Tracker_ArtifactFactory');
        Tracker_ArtifactFactory::setInstance($this->artifact_factory);

        $this->formelement_factory = mock('Tracker_FormElementFactory');
        Tracker_FormElementFactory::setInstance($this->formelement_factory);
    }

    public function tearDown() {
        parent::tearDown();
        Tracker_FormElementFactory::clearInstance();
    }

    public function itComputesDirectValues() {
        expect($this->dao)->getFieldValues()->once();
        stub($this->dao)->getFieldValues(array(233), 'effort')->returnsDar(
            array('id' => 750, 'type' => 'int', 'int_value' => 5),
            array('id' => 751, 'type' => 'int', 'int_value' => 15)
        );

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $this->assertEqual(20, $this->field->getComputedValue($this->user, $artifact));
    }

    public function itMakesOneDbCallPerGraphDepth() {
        expect($this->dao)->getFieldValues()->count(2);
        stub($this->dao)->getFieldValues(array(233), 'effort')->returnsDar(
            array('id' => 750, 'type' => 'int', 'int_value' => 5),
            array('id' => 751, 'type' => 'int', 'int_value' => 15),
            array('id' => 766, 'type' => 'computed'),
            array('id' => 777, 'type' => 'computed')
        );
        stub($this->dao)->getFieldValues(array(766, 777), 'effort')->returnsDar(
            array('id' => 752, 'type' => 'int', 'int_value' => 10),
            array('id' => 753, 'type' => 'int', 'int_value' => 10)
        );

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $this->assertEqual(40, $this->field->getComputedValue($this->user, $artifact));
    }

    public function itDoesntMakeLoopInGraph() {
        expect($this->dao)->getFieldValues()->count(2);
        stub($this->dao)->getFieldValues(array(233), 'effort')->returnsDar(
            array('id' => 750, 'type' => 'int', 'int_value' => 5),
            array('id' => 751, 'type' => 'int', 'int_value' => 15),
            array('id' => 766, 'type' => 'computed'),
            array('id' => 777, 'type' => 'computed')
        );
        stub($this->dao)->getFieldValues(array(766, 777), 'effort')->returnsDar(
            array('id' => 752, 'type' => 'int', 'int_value' => 10),
            array('id' => 753, 'type' => 'int', 'int_value' => 10),
            array('id' => 766, 'type' => 'computed'),
            array('id' => 233, 'type' => 'computed')
        );

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $this->assertEqual(40, $this->field->getComputedValue($this->user, $artifact));
    }

    /**
     * This use case highlights the case where a Release have 2 backlog elements
     * and 2 sprints. The backlog elements are also presents in the sprints backlog
     * each backlog element should be counted only once.
     */
    public function itDoesntCountTwiceTheFinalData() {
        stub($this->dao)->getFieldValues(array(233), 'effort')->returnsDar(
            array('id' => 750, 'type' => 'int', 'int_value' => 5),
            array('id' => 751, 'type' => 'int', 'int_value' => 15),
            array('id' => 766, 'type' => 'computed'),
            array('id' => 777, 'type' => 'computed')
        );
        stub($this->dao)->getFieldValues(array(766, 777), 'effort')->returnsDar(
            array('id' => 750, 'type' => 'int', 'int_value' => 5),
            array('id' => 751, 'type' => 'int', 'int_value' => 15),
            array('id' => 766, 'type' => 'computed')
        );

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $this->assertEqual(20, $this->field->getComputedValue($this->user, $artifact));
    }
}

class Tracker_FormElement_Field_Computed_getSoapValueTest extends TuleapTestCase {

    private $field;

    public function setUp() {
        parent::setUp();
        $id = $tracker_id = $parent_id = $description = $use_it = $scope = $required = $notifications = $rank = '';
        $name = 'foo';
        $label = 'Foo Bar';
        $this->field = partial_mock('Tracker_FormElement_Field_Computed', array('getComputedValue', 'userCanRead'), array($id, $tracker_id, $parent_id, $name, $label, $description, $use_it, $scope, $required, $notifications, $rank));

        $this->artifact = anArtifact()->build();
        $this->user = aUser()->build();
        $this->changeset = mock('Tracker_Artifact_Changeset');
        stub($this->changeset)->getArtifact()->returns($this->artifact);
    }

    public function itReturnsNullIfUserCannotAccessField() {
        expect($this->field)->userCanRead($this->user)->once();
        stub($this->field)->userCanRead()->returns(false);
        $this->assertIdentical($this->field->getSoapValue($this->user, $this->changeset), null);
    }

    public function itUsedTheComputedFieldValue() {
        stub($this->field)->userCanRead()->returns(true);

        expect($this->field)->getComputedValue($this->user, $this->artifact)->once();
        stub($this->field)->getComputedValue()->returns(9.0);

        $this->assertIdentical(
            $this->field->getSoapValue($this->user, $this->changeset),
            array(
                'field_name'  => 'foo',
                'field_label' => 'Foo Bar',
                'field_value' => array('value' => '9')
            )
        );
    }
}
