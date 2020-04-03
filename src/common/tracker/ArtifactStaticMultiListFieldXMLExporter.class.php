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

class ArtifactStaticMultiListFieldXMLExporter extends ArtifactFieldXMLExporter
{
    public const LABEL_VALUES_INDEX  = 'valueLabelList';
    public const TV3_VALUE_INDEX     = 'valueInt';
    public const TV3_TYPE            = 'MB_2';
    public const TV5_TYPE            = 'list';
    public const TV5_BIND            = 'static';

    public const SYS_VALUE_NONE_FR = 'Aucun';
    public const SYS_VALUE_NONE_EN = 'None';
    public const SYS_VALUE_ANY_EN  = 'Any';
    public const SYS_VALUE_ANY_FR  = 'Tous';

    /** @var ArtifactXMLExporterDao */
    private $dao;

    /** @var ArtifactMultiListCurrentValueExporter */
    private $current_value_exporter;

    public function __construct(
        ArtifactXMLNodeHelper $node_helper,
        ArtifactXMLExporterDao $dao,
        ArtifactMultiListCurrentValueExporter $current_value_exporter
    ) {
        parent::__construct($node_helper);
        $this->dao                    = $dao;
        $this->current_value_exporter = $current_value_exporter;
    }

    /**
     *
     * @param int $tracker_id
     * @param int $artifact_id
     * @param array $row
     *
     * @throws Exception_TV3XMLException
     */
    public function appendNode(DOMElement $changeset_node, $tracker_id, $artifact_id, array $row)
    {
        $all_labels = $this->getListValueLabels($row, $tracker_id);
        $values     = explode(',', $row['new_value']);
        $field_name = $row['field_name'];

        $field_node = $this->node_helper->createElement('field_change');
        $field_node->setAttribute('field_name', $field_name);
        $field_node->setAttribute('type', self::TV5_TYPE);
        $field_node->setAttribute('bind', self::TV5_BIND);

        foreach ($values as $value) {
            if ($this->valueCannotBeParsed($value, count($values), $field_name, $all_labels)) {
                throw new Exception_TV3XMLException("Unable to parse the value $value for the field $field_name");
            }

            $static_value_node = $this->node_helper->getNodeWithValue('value', $this->getValueLabel($value));
            $field_node->appendChild($static_value_node);
        }

        $changeset_node->appendChild($field_node);
    }

    private function getValueLabel($value)
    {
        if ($this->valueIsSystemValueNone($value)) {
            return '';
        }

        return $value;
    }

    public function getFieldValueIndex()
    {
        return self::LABEL_VALUES_INDEX;
    }

    public function getCurrentFieldValue(array $field_value_row, $tracker_id)
    {
        return $this->current_value_exporter->getCurrentFieldValue($field_value_row, $tracker_id);
    }

    /**
     * This method searches if the current value can be interpreted correctly
     *
     * We can have some strange cases in database side. It stores:
     *   A string comma separated if we select multiple values
     *   The label if its a unique value
     *   0 when the field is cleared without selecting any value
     *   'Any' or 'Tous' regarding the langage when the value is saved if the old value
     *     is a cleared field
     *
     * We can manage the first case because we are sur that there is only label
     * The two following cases are ambiguous : how to be sure that 0 is the label of the value
     * or the representation of a cleared field ?
     *
     * Then, if the unique value is an int, how to be sure that this numeric is a
     * label instead of an ID sometimes stored in the database ?
     *
     * If a label has a comma in its content, we are not able to manage it.
     *
     * Finally, when the label can be a system word, we don't know if it's the label
     * or a magic system word saved in the database.
     *
     * @param string $value
     * @param int    $number_of_values
     * @param string $field_name
     * @param array  $all_labels
     *
     * @return bool
     */
    private function valueCannotBeParsed($value, $number_of_values, $field_name, array $all_labels)
    {
        return $this->valueIsNotAnExistingLabel($value, $field_name, $all_labels) ||
               $this->valueIsSystemValueAny($value)                               ||
               is_numeric($value) && $number_of_values === 1;
    }

    private function valueIsSystemValueAny($value)
    {
        return $value === self::SYS_VALUE_ANY_EN  ||
               $value === self::SYS_VALUE_ANY_FR;
    }

    private function valueIsSystemValueNone($value)
    {
        return $value === self::SYS_VALUE_NONE_EN  ||
               $value === self::SYS_VALUE_NONE_FR;
    }

    private function valueIsNotAnExistingLabel($value, $field_name, array $all_labels)
    {
        if ($this->valueIsSystemValueNone($value)) {
            return false;
        }

        return ((bool) array_search($value, $all_labels)) === false;
    }

    private function getListValueLabels(array $field_value_row, $tracker_id)
    {
        $field_name        = $field_value_row['field_name'];
        $labels            = array();
        $values_label_rows = $this->dao->searchFieldValuesList($tracker_id, $field_name);
        foreach ($values_label_rows as $values_label_row) {
            $labels[$values_label_row['value_id']] = $values_label_row['value'];
        }

        return $labels;
    }
}
