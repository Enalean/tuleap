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
    }

    public function itComputesDirectValues() {
        stub($this->dao)->getFieldValues(array(233), 'effort')->returnsDar(
            array('type' => 'int', 'int_value' => 5),
            array('type' => 'int', 'int_value' => 15)
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
            array('type' => 'int', 'int_value' => 5),
            array('type' => 'int', 'int_value' => 15)
        );

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $this->assertEqual(20, $this->field->getComputedValue($this->user, $artifact));
    }

    public function itMakesOneDbCallPerGraphDepth() {
        expect($this->dao)->getFieldValues()->count(2);
        stub($this->dao)->getFieldValues(array(233), 'effort')->returnsDar(
            array('type' => 'int', 'int_value' => 5),
            array('type' => 'int', 'int_value' => 15),
            array('type' => 'computed', 'id' => 766),
            array('type' => 'computed', 'id' => 777)
        );
        stub($this->dao)->getFieldValues(array(766, 777), 'effort')->returnsDar(
            array('type' => 'int', 'int_value' => 10),
            array('type' => 'int', 'int_value' => 10)
        );

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $this->assertEqual(40, $this->field->getComputedValue($this->user, $artifact));
    }

    public function itDoesntMakeLoopInGraph() {
        expect($this->dao)->getFieldValues()->count(2);
        stub($this->dao)->getFieldValues(array(233), 'effort')->returnsDar(
            array('type' => 'int', 'int_value' => 5),
            array('type' => 'int', 'int_value' => 15),
            array('type' => 'computed', 'id' => 766),
            array('type' => 'computed', 'id' => 777)
        );
        stub($this->dao)->getFieldValues(array(766, 777), 'effort')->returnsDar(
            array('type' => 'int', 'int_value' => 10),
            array('type' => 'int', 'int_value' => 10),
            array('type' => 'computed', 'id' => 766),
            array('type' => 'computed', 'id' => 233)
        );

        $artifact = stub('Tracker_Artifact')->getId()->returns(233);
        $this->assertEqual(40, $this->field->getComputedValue($this->user, $artifact));
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

?>
