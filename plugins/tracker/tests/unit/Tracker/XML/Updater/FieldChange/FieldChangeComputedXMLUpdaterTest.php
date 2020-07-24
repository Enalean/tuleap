<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\XML\Updater\FieldChange;

use Tracker_FormElement_Field_Computed;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
final class FieldChangeComputedXMLUpdaterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \SimpleXMLElement
     */
    private $field_change;
    /**
     * @var FieldChangeComputedXMLUpdater
     */
    private $updater;

    protected function setUp(): void
    {
        $this->field_change = new \SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="capacity" type="computed">
                  </field_change>');
        $this->updater = new FieldChangeComputedXMLUpdater();
    }

    public function testItUpdatesWhenOnlyIsAutocomputedIsSet(): void
    {
        $submitted_value = [
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => '1'
        ];
        $this->updater->update($this->field_change, $submitted_value);

        $this->assertEquals('1', $this->field_change->is_autocomputed);
        $this->assertFalse(isset($this->field_change->manual_value));
    }

    public function testItUpdatesWhenAutocomputedIsSetAndManualValueEmpty(): void
    {
        $submitted_value = [
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => '1',
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL          => ''
        ];
        $this->updater->update($this->field_change, $submitted_value);

        $this->assertEquals('1', $this->field_change->is_autocomputed);
        $this->assertFalse(isset($this->field_change->manual_value));
    }

    public function testItUpdatesWhenOnlyAManualValueIsSet(): void
    {
        $submitted_value = [
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => '1.5'
        ];
        $this->updater->update($this->field_change, $submitted_value);

        $this->assertEquals('0', $this->field_change->is_autocomputed);
        $this->assertEquals(1.5, (float) $this->field_change->manual_value);
    }

    public function testItUpdatesWhenAManualValueIsSetAndIsAutocomputedDisabled(): void
    {
        $submitted_value = [
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => '0',
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => '1.5'
        ];
        $this->updater->update($this->field_change, $submitted_value);

        $this->assertEquals('0', $this->field_change->is_autocomputed);
        $this->assertEquals(1.5, (float) $this->field_change->manual_value);
    }

    public function testItDoesNotUpdateWhenAManualValueOrIsAutocomputedIsNotProvided(): void
    {
        $submitted_value = [];
        $this->updater->update($this->field_change, $submitted_value);

        $this->assertFalse(isset($this->field_change->is_autocomputed));
        $this->assertFalse(isset($this->field_change->manual_value));
    }

    public function testItUpdatesEverythingIfAManualValueIsSetAndIsAutocomputedIsEnabled(): void
    {
        $submitted_value = [
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => '1',
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => '1.5'
        ];
        $this->updater->update($this->field_change, $submitted_value);

        $this->assertEquals('1', $this->field_change->is_autocomputed);
        $this->assertEquals(1.5, (float) $this->field_change->manual_value);
    }

    public function testItUpdatesWhenOldValueIsManualAndValuesIsAutocomputed(): void
    {
        $submitted_value = [
            Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED => '1',
            Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL => ''
        ];

        $specific_field_change = new \SimpleXMLElement('<?xml version="1.0"?>
                  <field_change field_name="capacity" type="computed">
                    <manual_value><![CDATA[11]]></manual_value>
                  </field_change>');
        $this->updater->update($specific_field_change, $submitted_value);

        $this->assertEquals('1', $specific_field_change->is_autocomputed);
        $this->assertFalse(isset($specific_field_change->manual_value));
    }
}
