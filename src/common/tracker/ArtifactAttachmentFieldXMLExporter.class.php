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

class ArtifactAttachmentFieldXMLExporter extends ArtifactFieldXMLExporter
{
    public const TV3_TYPE            = 'attachment';
    public const TV5_TYPE            = 'file';
    public const XML_FILE_PREFIX = 'File';

    /** @var ArtifactXMLExporterDao */
    private $dao;

    public function __construct(ArtifactXMLNodeHelper $node_helper, ArtifactXMLExporterDao $dao)
    {
        parent::__construct($node_helper);
        $this->dao = $dao;
    }

    public function appendNode(DOMElement $changeset_node, $tracker_id, $artifact_id, array $row)
    {
        $new_attachment = $this->extractFirstDifference($row['old_value'], $row['new_value']);
        if ($new_attachment) {
            $dar = $this->dao->searchFile($artifact_id, $new_attachment, $row['mod_by'], $row['date']);
            if ($dar && $dar->rowCount() > 0) {
                $field_node = $this->node_helper->createElement('field_change');
                $field_node->setAttribute('field_name', 'attachment');
                $field_node->setAttribute('type', self::TV5_TYPE);
                foreach ($dar as $row_file) {
                    $field_node->appendChild($this->getNodeValueForFile($row_file['id']));
                }
                $this->appendPreviousAttachements($field_node, $artifact_id, $row['date'], $row['old_value']);
                $changeset_node->appendChild($field_node);
            } else {
                throw new Exception_TV3XMLAttachmentNotFoundException('new: ' . $new_attachment);
            }
        } else {
            $deleted_attachment = $this->extractFirstDifference($row['new_value'], $row['old_value']);
            throw new Exception_TV3XMLAttachmentNotFoundException('del: ' . $deleted_attachment . ' n:' . $row['new_value'] . ' o:' . $row['old_value']);
        }
    }

    private function appendPreviousAttachements(DOMElement $field_node, $artifact_id, $submitted_on, $old_value)
    {
        $previous_attachements = array_filter(explode(',', $old_value));
        foreach ($previous_attachements as $attachement) {
            $dar = $this->dao->searchFileBefore($artifact_id, $attachement, $submitted_on);
            if ($dar && $dar->rowCount() == 1) {
                $row_file = $dar->current();
                $field_node->appendChild($this->getNodeValueForFile($row_file['id']));
            }
        }
    }

    private function getNodeValueForFile($file_id)
    {
        $node = $this->node_helper->createElement('value');
        $node->setAttribute('ref', self::XML_FILE_PREFIX . $file_id);

        return $node;
    }

    /**
     * Given $reference_value = 'A.png' and $value_to_compare = 'A.png,zzz.pdf' returns 'zzz.pdf'
     *
     * Protip, before trying to refactor this with array_diff, think to the following
     * test case:
     * Given $reference_value = 'A.png' and $value_to_compare = 'A.png,A.png' returns 'A.png'
     * because 2 attachements can have same name!
     *
     * @param string $reference_value
     * @param string $value_to_compare
     */
    private function extractFirstDifference($reference_value, $value_to_compare)
    {
        $old_values_array = array_filter(explode(',', $reference_value));
        $new_values_array = array_filter(explode(',', $value_to_compare));

        for ($i = 0; $i < count($old_values_array); $i++) {
            if (isset($new_values_array[$i]) && $new_values_array[$i] == $old_values_array[$i]) {
                continue;
            } else {
                return '';
            }
        }
        if ($new_values_array[$i]) {
            return $new_values_array[$i];
        }
        return '';
    }

    public function getFieldValueIndex()
    {
        throw new Exception_TV3XMLException('Try to get artifact_value on a non value field: attachment');
    }
}
