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

class ArtifactUserMultiListFieldXMLExporter extends ArtifactAlphaNumFieldXMLExporter
{
    public const LABEL_VALUES_INDEX  = 'valueLabelList';
    public const TV3_TYPE            = 'MB_5';
    public const TV5_TYPE            = 'list';
    public const TV5_BIND            = 'users';

    public const SYS_VALUE_NONE_FR = 'Aucun';
    public const SYS_VALUE_NONE_EN = 'None';

    /** @var ArtifactMultiListCurrentValueExporter */
    private $current_value_exporter;

    public function __construct(
        ArtifactXMLNodeHelper $node_helper,
        ArtifactMultiListCurrentValueExporter $current_value_exporter
    ) {
        parent::__construct($node_helper);
        $this->current_value_exporter = $current_value_exporter;
    }

    public function appendNode(DOMElement $changeset_node, $tracker_id, $artifact_id, array $row)
    {
        $values = explode(',', $row['new_value']);

        $field_node = $this->getNode(self::TV5_TYPE, $row);
        $field_node->setAttribute('bind', self::TV5_BIND);
        foreach ($values as $value) {
            $user_node = $this->getNodeValue($this->getValueLabel($value));
            $this->node_helper->addUserFormatAttribute($user_node, false);
            $field_node->appendChild($user_node);
        }

        $changeset_node->appendChild($field_node);
    }

    protected function getValueLabel($value)
    {
        if ($this->valueIsSystemValueNone($value)) {
            return '';
        }

        return $value;
    }

    public function getCurrentFieldValue(array $field_value_row, $tracker_id)
    {
        return $this->current_value_exporter->getCurrentFieldValue($field_value_row, $tracker_id);
    }

    private function valueIsSystemValueNone($value)
    {
        return $value === self::SYS_VALUE_NONE_EN  ||
               $value === self::SYS_VALUE_NONE_FR;
    }

    public function getFieldValueIndex()
    {
        return self::LABEL_VALUES_INDEX;
    }

    public function isValueEqual($value1, $value2)
    {
        $value1 = explode(',', $value1);
        $value2 = explode(',', $value2);

        $value1 = array_map(array($this, 'getValueLabel'), $value1);
        $value2 = array_map(array($this, 'getValueLabel'), $value2);

        sort($value1);
        sort($value2);

        return $value1 == $value2;
    }
}
