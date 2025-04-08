<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

class TrackerXmlFieldsMapping_FromAnotherPlatform implements TrackerXmlFieldsMapping
{
    /**
     * @var array
     */
    private $xml_mapping;

    public function __construct(array $xml_mapping)
    {
        $this->xml_mapping = $xml_mapping;
    }

    public function getNewValueId($old_value_id)
    {
        $old_reference = $this->getOldValueReferenceFromOldValueId($old_value_id);

        if (isset($this->xml_mapping[$old_reference])) {
            $value = $this->xml_mapping[$old_reference];

            return $value->getId();
        }

        if (isset($this->xml_mapping[$old_value_id])) {
            $value = $this->xml_mapping[$old_value_id];

            return $value->getId();
        }

        throw new TrackerXmlFieldsMapping_ValueNotFoundException($old_value_id, $old_reference);
    }

    private function getOldValueReferenceFromOldValueId($old_value_id)
    {
        return Tracker_FormElement_Field_List_Value::XML_ID_PREFIX . $old_value_id;
    }

    public function getNewOpenValueId($old_value_id)
    {
        $old_reference = $this->getOldValueReferenceFromOldOpenValueId($old_value_id);

        if (isset($this->xml_mapping[$old_reference])) {
            $value = $this->xml_mapping[$old_reference];

            return $value->getId();
        }

        if (isset($this->xml_mapping[$old_value_id])) {
            $value = $this->xml_mapping[$old_value_id];

            return $value->getId();
        }

        throw new TrackerXmlFieldsMapping_ValueNotFoundException($old_value_id, $old_reference);
    }

    private function getOldValueReferenceFromOldOpenValueId($old_value_id)
    {
        if (substr($old_value_id, 0, 1) === Tracker_FormElement_Field_List_BindValue::BIND_PREFIX) {
            return substr_replace(
                $old_value_id,
                Tracker_FormElement_Field_List_Value::XML_ID_PREFIX,
                0,
                1,
            );
        } else {
            return $old_value_id;
        }
    }
}
