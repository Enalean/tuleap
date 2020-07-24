<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * I retrieve the current value(s) for a multi selectbox in
 * Tracker v3 migration purpose
 */
class ArtifactMultiListCurrentValueExporter
{
    public const LABEL_VALUES_INDEX         = 'valueLabelList';
    public const TV3_VALUE_INDEX            = 'valueInt';
    public const TV3_BIND_TO_USER_DATA_TYPE = '5';
    /** This happens when a MSB is changed into a SB and back to a MSB */
    public const TV3_ALTERNATE_BIND_TO_USER_DATA_TYPE = '2';

    /** @var array */
    private $current_field_values = [];

    /** @var array */
    private $user_names = [];

     /** @var array */
    private $labels = [];

    /** @var ArtifactXMLExporterDao */
    private $dao;

    public function __construct(ArtifactXMLExporterDao $dao)
    {
        $this->dao = $dao;
    }

    public function getCurrentFieldValue(array $field_value_row, $tracker_id)
    {
        $this->fetchAllLabels($field_value_row, $tracker_id);

        $field_name = $this->getFieldNameFromRow($field_value_row);

        if (! isset($this->current_field_values[$field_name])) {
            $this->initCurrentFieldValues($field_value_row);
        }

        $this->addCurrentValueLabel($field_value_row);

        return $this->current_field_values[$field_name] ?? null;
    }

    private function initCurrentFieldValues(array $field_value_row)
    {
        $field_name = $this->getFieldNameFromRow($field_value_row);

        $this->current_field_values[$field_name] = $field_value_row;
        $this->current_field_values[$field_name][self::LABEL_VALUES_INDEX] = null;
    }

    private function addCurrentValueLabel(array $field_value_row)
    {
        $field_name     = $this->getFieldNameFromRow($field_value_row);
        $existing_value = $this->getExistingValueForCurrentField($field_value_row);

        if (! $existing_value) {
            $current_value = $this->getCurrentValueLabel($field_value_row);
            $this->current_field_values[$field_name][self::LABEL_VALUES_INDEX] = $current_value;
            return;
        }

        $this->updateFieldsValues($field_value_row);
    }

    private function updateFieldsValues(array $field_value_row)
    {
        $field_name     = $this->getFieldNameFromRow($field_value_row);
        $current_value  = $this->getCurrentValueLabel($field_value_row);

        $existing_field_property_values = explode(',', $this->current_field_values[$field_name][self::LABEL_VALUES_INDEX]);

        if (! in_array($current_value, $existing_field_property_values)) {
            $this->current_field_values[$field_name][self::LABEL_VALUES_INDEX] .= ",$current_value";
        }
    }

    private function getCurrentValueLabel(array $field_value_row)
    {
        if ($this->fieldIsBindedToUser($field_value_row)) {
            if (! isset($this->user_names[$field_value_row[self::TV3_VALUE_INDEX]])) {
                return '';
            }
            return $this->user_names[$field_value_row[self::TV3_VALUE_INDEX]];
        }

        $field_name = $this->getFieldNameFromRow($field_value_row);

        return $this->labels[$field_name][$field_value_row[self::TV3_VALUE_INDEX]];
    }

    private function getExistingValueForCurrentField(array $field_value_row)
    {
        $field_name = $this->getFieldNameFromRow($field_value_row);

        return $this->current_field_values[$field_name][self::LABEL_VALUES_INDEX];
    }

    private function getFieldNameFromRow(array $field_value_row)
    {
        return $field_value_row['field_name'];
    }

    private function fetchAllUserNames()
    {
        if (empty($this->user_names)) {
            $this->user_names[100] = '';
            foreach ($this->dao->getAllUsers() as $user_info) {
                $this->user_names[$user_info['user_id']] = $user_info['user_name'];
            }
        }

        return $this->user_names;
    }

    private function fetchListValueLabels(array $field_value_row, $tracker_id)
    {
        $field_name = $this->getFieldNameFromRow($field_value_row);

        if (empty($this->labels[$field_name])) {
            $values_label_rows = $this->dao->searchFieldValuesList($tracker_id, $field_name);
            foreach ($values_label_rows as $values_label_row) {
                $this->labels[$field_name][$values_label_row['value_id']] = Encoding_SupportedXmlCharEncoding::getXMLCompatibleString($values_label_row['value']);
            }
        }
    }

    private function fieldIsBindedToUser(array $field_value_row)
    {
        return $field_value_row['data_type'] === self::TV3_BIND_TO_USER_DATA_TYPE || $this->isMSBThatWasChangedIntoASB($field_value_row);
    }

    private function isMSBThatWasChangedIntoASB(array $field_value_row)
    {
        return $field_value_row['data_type'] === self::TV3_ALTERNATE_BIND_TO_USER_DATA_TYPE &&
               $field_value_row['value_function'] != '';
    }

    private function fetchAllLabels(array $field_value_row, $tracker_id)
    {
        if ($this->fieldIsBindedToUser($field_value_row)) {
            $this->fetchAllUserNames();
        } else {
            $this->fetchListValueLabels($field_value_row, $tracker_id);
        }
    }
}
