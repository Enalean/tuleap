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

namespace Tuleap\Tracker\XML\Updater\FieldChange;

use Tracker_FormElement_Field_Computed;

require_once __DIR__ . '/../../../../bootstrap.php';

class FieldChangeComputedXMLUpdaterTest extends \TuleapTestCase
{
    /**
     * @var \SimpleXMLElement
     */
    private $field_change;
    /**
     * @var FieldChangeComputedXMLUpdater
     */
    private $updater;

    public function setUp()
    {
        parent::setUp();
        $this->field_change = new \SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="capacity" type="computed">
                  </field_change>');
        $this->updater = new FieldChangeComputedXMLUpdater();
    }

    public function itUpdatesWhenOnlyIsAutocomputedIsSet()
    {
        $submitted_value = array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => '1'
        );
        $this->updater->update($this->field_change, $submitted_value);

        $this->assertEqual($this->field_change->is_autocomputed, '1');
        $this->assertFalse(isset($this->field_change->manual_value));
    }

    public function itUpdatesWhenAutocomputedIsSetAndManualValueEmpty()
    {
        $submitted_value = array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => '1',
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL          => ''
        );
        $this->updater->update($this->field_change, $submitted_value);

        $this->assertEqual($this->field_change->is_autocomputed, '1');
        $this->assertFalse(isset($this->field_change->manual_value));
    }

    public function itUpdatesWhenOnlyAManualValueIsSet()
    {
        $submitted_value = array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => '1.5'
        );
        $this->updater->update($this->field_change, $submitted_value);

        $this->assertEqual($this->field_change->is_autocomputed, '0');
        $this->assertEqual($this->field_change->manual_value, 1.5);
    }

    public function itUpdatesWhenAManualValueIsSetAndIsAutocomputedDisabled()
    {
        $submitted_value = array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => '0',
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => '1.5'
        );
        $this->updater->update($this->field_change, $submitted_value);

        $this->assertEqual($this->field_change->is_autocomputed, '0');
        $this->assertEqual($this->field_change->manual_value, 1.5);
    }

    public function itDoesNotUpdateWhenAManualValueOrIsAutocomputedIsNotProvided()
    {
        $submitted_value = array();
        $this->updater->update($this->field_change, $submitted_value);

        $this->assertFalse(isset($this->field_change->is_autocomputed));
        $this->assertFalse(isset($this->field_change->manual_value));
    }

    public function itUpdatesEverythingIfAManualValueIsSetAndIsAutocomputedIsEnabled()
    {
        $submitted_value = array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => '1',
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => '1.5'
        );
        $this->updater->update($this->field_change, $submitted_value);

        $this->assertEqual($this->field_change->is_autocomputed, '1');
        $this->assertEqual($this->field_change->manual_value, 1.5);
    }

    public function itUpdatesWhenOldValueIsManualAndValuesIsAutocomputed()
    {
        $submitted_value = array(
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => '1',
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => ''
        );

        $specific_field_change = new \SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="capacity" type="computed">
                    <manual_value><![CDATA[11]]></manual_value>
                  </field_change>');
        $this->updater->update($specific_field_change, $submitted_value);

        $this->assertEqual($specific_field_change->is_autocomputed, '1');
        $this->assertFalse(isset($specific_field_change->manual_value));
    }
}
