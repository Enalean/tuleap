<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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


namespace Tuleap\Tracker\Artifact\XMLImport;

require_once __DIR__.'/../../bootstrap.php';

use Tracker_FormElement_Field_Computed;

class XMLImportFieldStrategyComputedTest extends \TuleapTestCase
{
    public function itShouldWorkWithAManualValue()
    {
        $field             = mock('Tracker_FormElement_Field_Computed');
        $user              = mock('PFUser');
        $xml_change        = new \SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="capacity" type="computed">
                    <manual_value>0</manual_value>
                  </field_change>');
        $strategy_computed = new XMLImportFieldStrategyComputed();

        $change_computed = $strategy_computed->getFieldData($field, $xml_change, $user, mock('Tracker_Artifact'));
        $expected_result = array(Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => '0');

        $this->assertIdentical($change_computed, $expected_result);
    }

    public function itShouldWorkWhenIsAutocomputed()
    {
        $field             = mock('Tracker_FormElement_Field_Computed');
        $user              = mock('PFUser');
        $xml_change        = new \SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="capacity" type="computed">
                    <is_autocomputed>1</is_autocomputed>
                  </field_change>');
        $strategy_computed = new XMLImportFieldStrategyComputed();

        $change_computed = $strategy_computed->getFieldData($field, $xml_change, $user, mock('Tracker_Artifact'));
        $expected_result = array(Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => '1');

        $this->assertIdentical($change_computed, $expected_result);
    }

    public function itShouldWorkWithAManualValueAndIsAutocomputed()
    {
        $field             = mock('Tracker_FormElement_Field_Computed');
        $user              = mock('PFUser');
        $xml_change        = new \SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="capacity" type="computed">
                    <manual_value></manual_value>
                    <is_autocomputed>1</is_autocomputed>
                  </field_change>');
        $strategy_computed = new XMLImportFieldStrategyComputed();

        $change_computed = $strategy_computed->getFieldData($field, $xml_change, $user, mock('Tracker_Artifact'));
        $expected_result = array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => '',
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => '1'
        );

        $this->assertIdentical($change_computed, $expected_result);
    }
}
