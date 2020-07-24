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

class ArtifactStaticListFieldXMLExporter extends ArtifactAlphaNumFieldXMLExporter
{
    public const TV3_DISPLAY_TYPE = 'SB';
    public const TV3_DATA_TYPE    = '2';
    public const TV3_VALUE_INDEX  = 'valueInt';
    public const TV3_TYPE         = 'SB_2';
    public const TV5_TYPE         = 'list';
    public const TV5_BIND         = 'static';

    public const SPECIAL_SEVERITY = 'severity';
    public const SPECIAL_STATUS   = 'status_id';

    /** @var ArtifactXMLExporterDao */
    protected $dao;
    private $artifact_field_value_not_accurate;

    public function __construct(ArtifactXMLNodeHelper $node_helper, ArtifactXMLExporterDao $dao)
    {
        parent::__construct($node_helper);
        $this->dao = $dao;
        $this->artifact_field_value_not_accurate = [
            self::SPECIAL_SEVERITY => true,
            self::SPECIAL_STATUS   => true,
        ];
    }

    public function appendNode(DOMElement $changeset_node, $tracker_id, $artifact_id, array $row)
    {
        $field_node = $this->getNode(self::TV5_TYPE, $row);
        $field_node->setAttribute('bind', self::TV5_BIND);
        $field_node->appendChild($this->getNodeValue($this->getValueLabel($tracker_id, $artifact_id, $row['field_name'], $row['new_value'])));
        $changeset_node->appendChild($field_node);
    }

    private function getValueLabel($tracker_id, $artifact_id, $field_name, $value)
    {
        if ($field_name == self::SPECIAL_SEVERITY && $value == 0) {
            return '';
        }
        if ($value == 100) {
            return '';
        }

        $values_list = $this->dao->searchFieldValuesList($tracker_id, $field_name);
        if (! $values_list) {
            return '';
        }

        foreach ($values_list as $row) {
            if ($row['value_id'] == $value) {
                return Encoding_SupportedXmlCharEncoding::getXMLCompatibleString($row['value']);
            }
        }

        throw new Exception_TV3XMLException("Unknown label for $artifact_id $value");
    }

    public function getCurrentFieldValue(array $field_value_row, $tracker_id)
    {
        if (! isset($this->artifact_field_value_not_accurate[$field_value_row['field_name']])) {
            return parent::getCurrentFieldValue($field_value_row, $tracker_id);
        }
    }

    public function getFieldValueIndex()
    {
        return self::TV3_VALUE_INDEX;
    }
}
