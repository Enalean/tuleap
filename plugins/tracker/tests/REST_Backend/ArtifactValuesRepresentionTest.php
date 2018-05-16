<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

require_once __DIR__.'/../bootstrap.php';

class ArtifactValuesRepresentionTest extends \TuleapTestCase
{
    public function itDoesNotFindAValueWhenNoneIsProvided()
    {
        $artifact_value_representation           = new ArtifactValuesRepresentation();
        $artifact_value_representation->field_id = 1;

        $expected_array = array('field_id' => 1);

        $this->assertEqual($artifact_value_representation->toArray(), $expected_array);
    }

    public function itAcceptsEmptyArrayAsValue()
    {
        $artifact_value_representation           = new ArtifactValuesRepresentation();
        $artifact_value_representation->field_id = 1;
        $artifact_value_representation->value    = array();

        $expected_array = array('field_id' => 1, 'value' => array());

        $this->assertEqual($artifact_value_representation->toArray(), $expected_array);
    }

    public function itAcceptsEmptyStringAsValue()
    {
        $artifact_value_representation           = new ArtifactValuesRepresentation();
        $artifact_value_representation->field_id = 1;
        $artifact_value_representation->value    = '';

        $expected_array = array('field_id' => 1, 'value' => '');

        $this->assertEqual($artifact_value_representation->toArray(), $expected_array);
    }

    public function itAccepts0StringAsValue()
    {
        $artifact_value_representation           = new ArtifactValuesRepresentation();
        $artifact_value_representation->field_id = 1;
        $artifact_value_representation->value    = '0';

        $expected_array = array('field_id' => 1, 'value' => '0');

        $this->assertEqual($artifact_value_representation->toArray(), $expected_array);
    }

    public function itAccepts0IntegerAsValue()
    {
        $artifact_value_representation           = new ArtifactValuesRepresentation();
        $artifact_value_representation->field_id = 1;
        $artifact_value_representation->value    = 0;

        $expected_array = array('field_id' => 1, 'value' => 0);

        $this->assertEqual($artifact_value_representation->toArray(), $expected_array);
    }

    public function itAccepts0FloatAsValue()
    {
        $artifact_value_representation           = new ArtifactValuesRepresentation();
        $artifact_value_representation->field_id = 1;
        $artifact_value_representation->value    = 0.0;

        $expected_array = array('field_id' => 1, 'value' => 0.0);

        $this->assertEqual($artifact_value_representation->toArray(), $expected_array);
    }
}
