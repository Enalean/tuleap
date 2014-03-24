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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class ArtifactStaticMultiListFieldXMLExporter extends ArtifactFieldXMLExporter {
    const LABEL_VALUES_INDEX  = 'valueLabelList';
    const TV3_VALUE_INDEX     = 'valueInt';
    const TV3_TYPE            = 'MB_2';
    const TV5_TYPE            = 'list';
    const TV5_BIND            = 'static';

    const SYS_VALUE_NONE_FR = 'Aucun';
    const SYS_VALUE_NONE_EN = 'None';
    const SYS_VALUE_ANY_EN  = 'Any';
    const SYS_VALUE_ANY_FR  = 'Tous';

    /** @var array() */
    private $labels = array();

    /** @var ArtifactXMLExporterDao */
    private $dao;

    /** @var array */
    private $current_field_values = array();

    public function __construct(ArtifactXMLNodeHelper $node_helper, ArtifactXMLExporterDao $dao) {
        parent::__construct($node_helper);
        $this->dao = $dao;
    }

    public function appendNode(DOMElement $changeset_node, $tracker_id, $artifact_id, array $row) {
        $values     = explode(',', $row['new_value']);
        $field_name = $this->getFieldNameFromRow($row);

        $field_node = $this->node_helper->createElement('field_change');
        $field_node->setAttribute('field_name', $field_name);
        $field_node->setAttribute('type', self::TV5_TYPE);
        $field_node->setAttribute('bind', self::TV5_BIND);
        foreach ($values as $value) {
            $static_value_node = $this->node_helper->getNodeWithValue('value', $this->getValueLabel($value));
            $field_node->appendChild($static_value_node);
        }

        $changeset_node->appendChild($field_node);
    }

    private function getValueLabel($value) {
        return $value;
    }

    public function getFieldValueIndex() {
        return self::LABEL_VALUES_INDEX;
    }

    public function getCurrentFieldValue(array $field_value_row, $tracker_id) {
        $this->getListValueLabels($tracker_id, $field_value_row);

        $field_name = $this->getFieldNameFromRow($field_value_row);

        if (! isset($this->current_field_values[$field_name])) {
            $this->initCurrentFieldValues($field_value_row);
        }

        $this->addCurrentValueLabel($field_value_row);

        return $this->current_field_values[$field_name];
    }

    private function addCurrentValueLabel(array $field_value_row) {
        $field_name     = $this->getFieldNameFromRow($field_value_row);
        $existing_value = $this->getExistingValueForCurrentField($field_value_row);

        if (! $existing_value) {
            $current_value = $this->getCurrentValueLabel($field_value_row);
            $this->current_field_values[$field_name][self::LABEL_VALUES_INDEX] = $current_value;
            return;
        }

        $this->updateFieldsValues($field_value_row);
    }

    private function updateFieldsValues(array $field_value_row) {
        $field_name     = $this->getFieldNameFromRow($field_value_row);
        $current_value  = $this->getCurrentValueLabel($field_value_row);

        $this->current_field_values[$field_name][self::LABEL_VALUES_INDEX] .= ",$current_value";
    }

    private function getListValueLabels($tracker_id, array $field_value_row) {
        $field_name = $this->getFieldNameFromRow($field_value_row);

        if (empty($this->labels[$field_name])) {
            $values_label_rows = $this->dao->searchFieldValuesList($tracker_id, $field_name);
            foreach ($values_label_rows as $values_label_row) {
                $this->labels[$field_name][$values_label_row['value_id']] = $values_label_row['value'];
            }
        }

        return $this->labels;
    }

    private function initCurrentFieldValues(array $field_value_row) {
        $field_name = $this->getFieldNameFromRow($field_value_row);

        $this->current_field_values[$field_name] = $field_value_row;
        $this->current_field_values[$field_name][self::LABEL_VALUES_INDEX] = null;
    }

    private function getFieldNameFromRow(array $field_value_row) {
        return $field_value_row['field_name'];
    }

    private function getExistingValueForCurrentField(array $field_value_row) {
        $field_name = $this->getFieldNameFromRow($field_value_row);

        return $this->current_field_values[$field_name][self::LABEL_VALUES_INDEX];
    }

    private function getCurrentValueLabel(array $field_value_row) {
        $field_name = $this->getFieldNameFromRow($field_value_row);

        return $this->labels[$field_name][$field_value_row[self::TV3_VALUE_INDEX]];
    }

}
