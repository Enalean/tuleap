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

class ArtifactFieldFactoryXMLExporter {
    /** @var Array */
    private $fields = array();

    public function __construct(ArtifactXMLExporterDao $dao, ArtifactXMLNodeHelper $node_helper) {
        $this->fields = array(
            ArtifactAttachmentFieldXMLExporter::TV3_TYPE      => new ArtifactAttachmentFieldXMLExporter($node_helper, $dao),
            ArtifactCCFieldXMLExporter::TV3_TYPE              => new ArtifactCCFieldXMLExporter($node_helper),
            ArtifactStringFieldXMLExporter::TV3_TYPE          => new ArtifactStringFieldXMLExporter($node_helper),
            ArtifactTextFieldXMLExporter::TV3_TYPE            => new ArtifactTextFieldXMLExporter($node_helper),
            ArtifactIntegerFieldXMLExporter::TV3_TYPE         => new ArtifactIntegerFieldXMLExporter($node_helper),
            ArtifactFloatFieldXMLExporter::TV3_TYPE           => new ArtifactFloatFieldXMLExporter($node_helper),
            ArtifactDateFieldXMLExporter::TV3_TYPE            => new ArtifactDateFieldXMLExporter($node_helper),
            ArtifactStaticListFieldXMLExporter::TV3_TYPE      => new ArtifactStaticListFieldXMLExporter($node_helper, $dao),
            ArtifactUserListFieldXMLExporter::TV3_TYPE        => new ArtifactUserListFieldXMLExporter($node_helper, $dao),
            ArtifactStaticMultiListFieldXMLExporter::TV3_TYPE => new ArtifactStaticMultiListFieldXMLExporter($node_helper, $dao),
        );
    }

    public function appendValueByType(DOMElement $changeset_node, $tracker_id, $artifact_id, array $history_row) {
        $this->getFieldByHistoryRow($history_row)->appendNode($changeset_node, $tracker_id, $artifact_id, $history_row);
    }

    private function getFieldByHistoryRow(array $history_row) {
        return $this->getField($history_row['field_name'], $history_row['display_type'], $history_row['data_type']);
    }

    /**
     *
     * @param string $field_name
     * @param string $display_type
     * @param string $data_type
     *
     * @return ArtifactFieldXMLExporter
     *
     * @throws Exception_TV3XMLUnknownFieldTypeException
     */
    private function getField($field_name, $display_type, $data_type) {
        $index = $this->getFieldType($field_name, $display_type, $data_type);
        if (isset($this->fields[$index])) {
            return $this->fields[$index];
        }
        throw new Exception_TV3XMLUnknownFieldTypeException($field_name);
    }

    private function getFieldType($field_name, $display_type, $data_type) {
        if ($display_type != null && $data_type != null) {
            return $display_type.'_'.$data_type;
        } elseif (isset($this->fields[$field_name])) {
            return $field_name;
        }
        throw new Exception_TV3XMLUnknownFieldTypeException($field_name);
    }

    public function getFieldValue(array $field_value_row) {
        return $field_value_row[$this->getFieldByHistoryRow($field_value_row)->getFieldValueIndex()];
    }

    public function getCurrentFieldValue(array $field_value_row, $tracker_id) {
        $field = $this->getField(
            $field_value_row['field_name'],
            $field_value_row['display_type'],
            $field_value_row['data_type']
        );

        return $field->getCurrentFieldValue($field_value_row, $tracker_id);
    }
}
