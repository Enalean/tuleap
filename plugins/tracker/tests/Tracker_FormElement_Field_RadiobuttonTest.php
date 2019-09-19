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

class Tracker_FormElement_Field_RadiobuttonHTMLTest extends Tracker_FormElement_Field_Radiobutton
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

class Tracker_FormElement_Field_RadiobuttonTest extends TuleapTestCase
{

    public function itIsNotNoneWhenArrayContainsAValue()
    {
        $field = aRadiobuttonField()->build();
        $this->assertFalse($field->isNone(array('1' => '555')));
    }

    public function itHasNoChangesWhenSubmittedValuesAreTheSameAsStored()
    {
        $previous = stub('Tracker_Artifact_ChangesetValue_List')->getValue()->returns(array(5123));
        $field = aRadiobuttonField()->build();
        $this->assertFalse($field->hasChanges(mock('Tracker_Artifact'), $previous, array('5123')));
    }

    public function itDetectsChangesEvenWhenCSVImportValueIsNull()
    {
        $previous = stub('Tracker_Artifact_ChangesetValue_List')->getValue()->returns(array(5123));
        $field = aRadiobuttonField()->build();
        $this->assertTrue($field->hasChanges(mock('Tracker_Artifact'), $previous, null));
    }

    public function itHasChangesWhenSubmittedValuesContainsDifferentValues()
    {
        $previous = stub('Tracker_Artifact_ChangesetValue_List')->getValue()->returns(array('5123'));
        $field = aRadiobuttonField()->build();
        $this->assertTrue($field->hasChanges(mock('Tracker_Artifact'), $previous, array('5122')));
    }

    public function itReplaceCSVNullValueByNone()
    {
        $field = aRadiobuttonField()->build();
        $this->assertEqual(
            $field->getFieldDataFromCSVValue(null, null),
            Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID
        );
    }
}
