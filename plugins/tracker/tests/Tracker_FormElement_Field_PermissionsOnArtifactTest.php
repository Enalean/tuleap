<?php
/**
 * Copyright (c) Enalean, 2015 - 2017. All Rights Reserved.
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

class Tracker_FormElement_Field_PermissionsOnArtifactTest extends TuleapTestCase
{
    /**
     * @var Tracker_FormElement_Field_PermissionsOnArtifact
     */
    private $field;

    /**
     * @var Tracker_Artifact
     */
    private $artifact;

    public function setUp()
    {
        $this->artifact = anArtifact()->withId(101)->build();
        $this->field    = partial_mock(
            'Tracker_FormElement_Field_PermissionsOnArtifact',
            array(
                'addRequiredError',
                'isRequired'
            )
        );
    }

    public function itThrowsAnExceptionWhenReturningValueIndexedByFieldName()
    {
        $this->expectException('Tracker_FormElement_RESTValueByField_NotImplementedException');

        $value = ['some_value'];
        $this->field->getFieldDataFromRESTValueByField($value);
    }

    public function itReturnsTrueWhenCheckboxIsCheckedAndAUgroupIsSelected()
    {
        stub($this->field)->isRequired()->returns(false);
        $submitted_values = array(
            'use_artifact_permissions' => true,
            'u_groups'                 => array(ForgeAccess::ANONYMOUS)
        );
        $this->assertTrue(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($this->artifact, $submitted_values)
        );
    }

    public function itReturnsTrueWhenCheckboxIsUnchecked()
    {
        stub($this->field)->isRequired()->returns(false);
        $submitted_values = array(
            'use_artifact_permissions' => false
        );
        $this->assertTrue(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($this->artifact, $submitted_values)
        );
    }

    public function itReturnsTrueWhenArrayIsEmpty()
    {
        stub($this->field)->isRequired()->returns(false);
        $submitted_values = array();
        $this->assertTrue(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($this->artifact, $submitted_values)
        );
    }

    public function itReturnsFalseWhenCheckboxIsCheckedAndNoUGroupIsSelected()
    {
        stub($this->field)->isRequired()->returns(false);
        $submitted_values = array(
            'use_artifact_permissions' => true,
            'u_groups'                 => array()
        );

        $this->assertFalse(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($this->artifact, $submitted_values)
        );
    }

    public function itReturnsFalseWhenFieldIsRequiredAndNoValueAreSet()
    {
        stub($this->field)->isRequired()->returns(true);
        $submitted_values = array();
        $this->assertFalse(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($this->artifact, $submitted_values)
        );
    }

    public function itReturnsFalseWhenFieldIsRequiredAndValueAreNotCorrectlySet()
    {
        stub($this->field)->isRequired()->returns(true);
        $submitted_values = array(
            'use_artifact_permissions' => true,
            'u_groups'                 => array()
        );
        $this->assertFalse(
            $this->field->validateFieldWithPermissionsAndRequiredStatus($this->artifact, $submitted_values)
        );
    }
}
