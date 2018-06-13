<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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

class Tracker_FormElement_Field_Selectbox_getFieldDataFromSoapValue extends TuleapTestCase {
    private $field;
    private $bind;

    public function setUp() {
        parent::setUp();
        $this->bind  = mock('Tracker_FormElement_Field_List_Bind_Static');
        $this->field = aSelectBoxField()->withBind($this->bind)->build();
    }

    public function itFallsBackToValueStringProcessing() {
        $soap_value = (object) array(
            'field_name'  => '',
            'field_label' => '',
            'field_value' => (object) array(
                'value' => 'Zoulou'
            )
        );

        expect($this->bind)->getFieldData('Zoulou', false)->once();
        stub($this->bind)->getFieldData()->returns(1586);

        $this->assertEqual(1586, $this->field->getFieldDataFromSoapValue($soap_value));
    }

    public function itAllowsValueStringToBeEmpty() {
        $soap_value = (object) array(
            'field_name'  => '',
            'field_label' => '',
            'field_value' => (object) array(
                'value' => ''
            )
        );

        expect($this->bind)->getFieldData('', false)->once();
        stub($this->bind)->getFieldData()->returns(null);

        $this->assertEqual(null, $this->field->getFieldDataFromSoapValue($soap_value));
    }

    public function itExtractsDataFromBindValue() {
        $soap_value = (object) array(
            'field_name'  => '',
            'field_label' => '',
            'field_value' => (object) array(
                'bind_value' => array(
                    (object) array('bind_value_id' => 1586, 'bind_value_label' => '')
                )
            )
        );

        $this->assertEqual(1586, $this->field->getFieldDataFromSoapValue($soap_value));
    }

    public function itPrefersBindValueOnStringValue() {
        $soap_value = (object) array(
            'field_name'  => '',
            'field_label' => '',
            'field_value' => (object) array(
                'value'      => '1586',
                'bind_value' => array(
                    (object) array('bind_value_id' => 2331, 'bind_value_label' => '')
                )
            )
        );
        $this->assertEqual(2331, $this->field->getFieldDataFromSoapValue($soap_value));
    }
}

class Tracker_FormElement_Field_Selectbox__getSoapValueTest extends TuleapTestCase {
    private $user;

    public function setUp() {
        parent::setUp();
        $this->user = mock('PFUser');


        $id = $tracker_id = $parent_id = $description = $use_it = $scope = $required = $notifications = $rank = '';
        $name = 'foo';
        $label = 'Foo Bar';
        $this->field = partial_mock('Tracker_FormElement_Field_Selectbox', array('userCanRead'), array($id, $tracker_id, $parent_id, $name, $label, $description, $use_it, $scope, $required, $notifications, $rank));

        $this->list_values = array(
            aFieldListStaticValue()->withId(100)->withLabel('None')->build(),
            aFieldListStaticValue()->withId(101)->withLabel('Bla')->build()
        );
        $this->changeset_value = new Tracker_Artifact_ChangesetValue_List('whatever', mock('Tracker_Artifact_Changeset'), $this->field, true, $this->list_values);
        $this->last_changeset  = mock('Tracker_Artifact_Changeset');
        stub($this->last_changeset)->getValue($this->field)->returns($this->changeset_value);
    }

    public function itReturnsNullIfUserCannotAccessField() {
        expect($this->field)->userCanRead($this->user)->once();
        stub($this->field)->userCanRead()->returns(false);
        $this->assertIdentical($this->field->getSoapValue($this->user, $this->last_changeset), null);
    }

    public function itHasAnListValueReturnedAsAnArrayOfFieldBindValue() {
        stub($this->field)->userCanRead()->returns(true);
        $this->assertIdentical(
            $this->field->getSoapValue($this->user, $this->last_changeset),
            array(
                'field_name' => 'foo',
                'field_label' => 'Foo Bar',
                'field_value' => array(
                    'bind_value' => array(
                        array(
                            'bind_value_id'    => 100,
                            'bind_value_label' => 'None'
                        ),
                        array(
                            'bind_value_id'    => 101,
                            'bind_value_label' => 'Bla'
                        ),
                    )
                )
            )
        );
    }
}

class Tracker_FormElement_Field_Selectbox_RESTTests extends TuleapTestCase {

    public function itThrowsAnExceptionWhenReturningValueIndexedByFieldName() {
        $field = new Tracker_FormElement_Field_Selectbox(
            1,
            101,
            null,
            'field_sb',
            'Field SB',
            '',
            1,
            'P',
            true,
            '',
            1
        );

        $this->expectException('Tracker_FormElement_RESTValueByField_NotImplementedException');

        $value = ['some_value'];

        $field->getFieldDataFromRESTValueByField($value);
    }
}
