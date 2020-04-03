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

use SimpleXMLElement;
use Tracker_XML_Updater_FieldChange_FieldChangeXMLUpdater;
use Tracker_FormElement_Field_Computed;

class FieldChangeComputedXMLUpdater implements Tracker_XML_Updater_FieldChange_FieldChangeXMLUpdater
{
    public function update(SimpleXMLElement $field_change_xml, $submitted_value)
    {
        if (
            ! isset($submitted_value[Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED]) &&
            ! isset($submitted_value[Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL])
        ) {
            return;
        }

        if (
            isset($submitted_value[Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL]) &&
                $submitted_value[Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL] !== ''
        ) {
            $field_change_xml->manual_value = (float) $submitted_value[Tracker_FormElement_Field_Computed::FIELD_VALUE_MANUAL];
        } else {
            unset($field_change_xml->manual_value);
        }

        $field_change_xml->is_autocomputed = '0';
        if (
            isset($submitted_value[Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED]) &&
                $submitted_value[Tracker_FormElement_Field_Computed::FIELD_VALUE_IS_AUTOCOMPUTED] == 1
        ) {
            $field_change_xml->is_autocomputed = '1';
        }
    }
}
