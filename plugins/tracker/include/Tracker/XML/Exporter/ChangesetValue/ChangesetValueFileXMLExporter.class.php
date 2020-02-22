<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\Tracker\FormElement\Field\File\IdForXMLImportExportConvertor;
use Tuleap\Tracker\XML\Exporter\FileInfoXMLExporter;

class Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporter extends Tracker_XML_Exporter_ChangesetValue_ChangesetValueXMLExporter
{
    /**
     * @var FileInfoXMLExporter
     */
    private $file_info_xml_exporter;

    public function __construct(FileInfoXMLExporter $file_info_xml_exporter)
    {
        $this->file_info_xml_exporter = $file_info_xml_exporter;
    }

    protected function getFieldChangeType()
    {
        return 'file';
    }

    public function export(
        SimpleXMLElement $artifact_xml,
        SimpleXMLElement $changeset_xml,
        Tracker_Artifact $artifact,
        Tracker_Artifact_ChangesetValue $changeset_value
    ) {
        $field_change = $this->createFieldChangeNodeInChangesetNode(
            $changeset_value,
            $changeset_xml
        );

        $files = $changeset_value->getFiles();

        if (! $files) {
            $this->appendEmptyValueToFieldChangeNode($field_change);

            return;
        }

        array_walk(
            $files,
            function (Tracker_FileInfo $file_info, $index, SimpleXMLElement $field_xml) {
                $this->appendFileToFieldChangeNode($file_info, $index, $field_xml);
            },
            $field_change
        );

        foreach ($files as $file) {
            $this->file_info_xml_exporter->add($artifact, $file);
        }
    }

    private function appendFileToFieldChangeNode(
        Tracker_FileInfo $file_info,
        $index,
        SimpleXMLElement $field_xml
    ) {
        $node = $field_xml->addChild('value');
        $node->addAttribute('ref', $this->getFileInfoIdForXML($file_info));
    }

    private function appendEmptyValueToFieldChangeNode(SimpleXMLElement $field_xml)
    {
        $field_xml->addChild('value');
    }

    private function getFileInfoIdForXML(Tracker_FileInfo $file_info)
    {
        return IdForXMLImportExportConvertor::convertFileInfoIdToXMLId((int) $file_info->getId());
    }
}
