<?php
/**
 * Copyright Enalean (c) 2011 - Present. All rights reserved.
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

class Tracker_FormElement_Field_CheckboxHTMLTest extends Tracker_FormElement_Field_Checkbox
{
    public function __construct()
    {
        $id = $tracker_id = $parent_id = $name = $label = $description = $use_it = $scope = $required = $notifications = $rank = null;
        parent::__construct($id, $tracker_id, $parent_id, $name, $label, $description, $use_it, $scope, $required, $notifications, $rank);
    }

    public function fetchFieldValue(Tracker_FormElement_Field_List_Value $value, $name, $is_selected)
    {
        return parent::fetchFieldValue($value, $name, $is_selected);
    }
}

class Tracker_FormElement_Field_CheckboxTest extends TuleapTestCase
{

    public function itIsNoneWhenArrayIsFullOfZero()
    {
        $field = aCheckboxField()->build();
        $this->assertTrue($field->isNone(array('0', '0', '0')));
    }

    public function itIsNotNoneWhenArrayContainsAValue()
    {
        $field = aCheckboxField()->build();
        $this->assertFalse($field->isNone(array('1' => '0', '2' => '53')));
    }

    public function itHasNoChangesWhenSubmittedValuesAreTheSameAsStored()
    {
        $previous = stub('Tracker_Artifact_ChangesetValue_List')->getValue()->returns(array(5123, 5125));
        $field = aCheckboxField()->build();
        $this->assertFalse($field->hasChanges(mock('Tracker_Artifact'), $previous, array('5123', '5125')));
    }

    public function itHasNoChangesWhenSubmittedValuesContainsZero()
    {
        $previous = stub('Tracker_Artifact_ChangesetValue_List')->getValue()->returns(array(5123, 5125));
        $field = aCheckboxField()->build();
        $this->assertFalse($field->hasChanges(mock('Tracker_Artifact'), $previous, array('5123', '0', '5125')));
    }

    public function itDetectsChangesEvenWhenCSVImportValueIsNull()
    {
        $previous = stub('Tracker_Artifact_ChangesetValue_List')->getValue()->returns(array(5123, 5125));
        $field = aCheckboxField()->build();
        $this->assertTrue($field->hasChanges(mock('Tracker_Artifact'), $previous, null));
    }

    public function itHasChangesWhenSubmittedValuesContainsDifferentValues()
    {
        $previous = stub('Tracker_Artifact_ChangesetValue_List')->getValue()->returns(array('5123', '5125'));
        $field = aCheckboxField()->build();
        $this->assertTrue($field->hasChanges(mock('Tracker_Artifact'), $previous, array('5123', '0', '5122')));
    }

    public function itHasAnHiddenFieldForEachCheckbox()
    {
        $value = aFieldListStaticValue()->withId(55)->withLabel('bla')->build();

        $field = new Tracker_FormElement_Field_CheckboxHTMLTest();
        $field->setBind(mock('Tracker_FormElement_Field_List_Bind_Static'));
        $html = $field->fetchFieldValue($value, 'lename', false);
        $this->assertPattern('/<input type="hidden" lename/', $html);
    }

    public function itReplaceCSVNullValueByNone()
    {
        $field = aCheckboxField()->build();
        $this->assertEqual(
            $field->getFieldDataFromCSVValue(null, null),
            array(Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID)
        );
    }
}
