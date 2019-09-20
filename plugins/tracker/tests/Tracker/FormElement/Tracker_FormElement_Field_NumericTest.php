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
require_once __DIR__.'/../../bootstrap.php';

class Tracker_FormElement_Field_Numeric_GetComputedValueTest extends TuleapTestCase
{

    public function itDelegatesRetrievalOfTheOldValueToTheDaoWhenNoTimestampGiven()
    {
        $user           = aUser()->build();
        $value_dao      = stub('Tracker_FormElement_Field_Value_FloatDao')->getLastValue()->returns(array('value' => '123.45'));
        $artifact       = aMockArtifact()->build();
        $field          = partial_mock('Tracker_FormElement_Field_Float', array('getId', 'userCanRead', 'getValueDao'));
        stub($field)->userCanRead($user)->returns(true);
        stub($field)->getValueDao()->returns($value_dao);

        $actual_value   = $field->getComputedValue($user, $artifact);
        $this->assertEqual('123.45', $actual_value);
    }

    public function itDelegatesRetrievalOfTheOldValueToTheDaoWhenGivenATimestamp()
    {
        $artifact_id    = 4528;
        $field_id       = 195;
        $user           = aUser()->build();
        $artifact_value = stub('Tracker_Artifact_ChangesetValue_Float')->getValue()->returns(123.45);
        $value_dao      = mock('Tracker_FormElement_Field_Value_FloatDao');
        $artifact       = aMockArtifact()->withId($artifact_id)->withValue($artifact_value)->build();
        $field          = TestHelper::getPartialMock('Tracker_FormElement_Field_Float', array('getId', 'getValueDao', 'userCanRead'));
        $timestamp      = 9340590569;
        $value          = 67.89;

        stub($field)->getId()->returns($field_id);
        stub($field)->getValueDao()->returns($value_dao);
        stub($field)->userCanRead($user)->returns(true);
        stub($value_dao)->getValueAt($artifact_id, $field_id, $timestamp)->returns(array('value' => $value));

        $this->assertIdentical($value, $field->getComputedValue($user, $artifact, $timestamp));
    }

    public function itReturnsZeroWhenUserDoesntHavePermissions()
    {
        $user           = aUser()->build();
        $artifact_value = stub('Tracker_Artifact_ChangesetValue_Float')->getValue()->returns(123.45);
        $artifact       = aMockArtifact()->withValue($artifact_value)->build();
        $field          = TestHelper::getPartialMock('Tracker_FormElement_Field_Float', array('userCanRead'));
        stub($field)->userCanRead($user)->returns(false);

        $actual_value = $field->getComputedValue($user, $artifact);
        $this->assertEqual(0, $actual_value);
    }
}
