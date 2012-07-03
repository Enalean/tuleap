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

require_once dirname(__FILE__).'/../../builders/aMockArtifact.php';
require_once dirname(__FILE__).'/../../builders/aField.php';

class Tracker_FormElement_Field_Numeric_GetComputedValueTest extends TuleapTestCase {
    
    public function itReturnsTheArtifactCurrentValueWhenNoTimestampGiven() {
        $user           = aUser()->build();
        $artifact_value = stub('Tracker_Artifact_ChangesetValue_Float')->getValue()->returns(123.45);
        $artifact       = aMockArtifact()->withValue($artifact_value)->build();
        $field          = aFloatField()->build();
        $actual_value   = $field->getComputedValue($user, $artifact);
        
        $this->assertEqual($actual_value, 123.45);
    }
}

?>
