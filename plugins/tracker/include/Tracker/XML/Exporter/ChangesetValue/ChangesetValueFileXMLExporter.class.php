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

class Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporter extends Tracker_XML_Exporter_ChangesetValue_ChangesetValueXMLExporter {

    const ID_PREFIX = 'fileinfo_';

    /**
     * @var Tracker_XML_Exporter_FilePathXMLExporter
     */
    private $path_exporter;

    public function __construct(Tracker_XML_Exporter_FilePathXMLExporter $path_exporter) {
        $this->path_exporter = $path_exporter;
    }

    protected function getFieldChangeType() {
        return 'file';
    }

    public function export(
        SimpleXMLElement $artifact_xml,
        SimpleXMLElement $changeset_xml,
        Tracker_Artifact $artifact,
        Tracker_Artifact_ChangesetValue $changeset_value
    ) {

        if (! $this->isCurrentChangesetTheLastChangeset($artifact, $changeset_value)) {
            return;
        }

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
            array($this, 'appendFileToFieldChangeNode'),
            $field_change
        );

        array_walk(
            $files,
            array($this, 'appendFileToArtifactNode'),
            $artifact_xml
        );
    }

    private function isCurrentChangesetTheLastChangeset(
        Tracker_Artifact $artifact,
        Tracker_Artifact_ChangesetValue $current_changeset_value
    ) {
        $file_field     = $current_changeset_value->getField();
        $last_changeset = $artifact->getLastChangeset();

        if (! $last_changeset) {
            return false;
        }

        $last_changeset_value = $last_changeset->getValue($file_field);

        if (! $last_changeset_value) {
            return false;
        }

        return ($last_changeset_value->getId() === $current_changeset_value->getId());
    }

    private function appendFileToFieldChangeNode(
        Tracker_FileInfo $file_info,
        $index,
        SimpleXMLElement $field_xml
    ) {
        $node = $field_xml->addChild('value');
        $node->addAttribute('ref', $this->getFileInfoIdForXML($file_info));
    }

    private function appendEmptyValueToFieldChangeNode(SimpleXMLElement $field_xml) {
        $field_xml->addChild('value');
    }

    private function appendFileToArtifactNode(
        Tracker_FileInfo $file_info,
        $index,
        SimpleXMLElement $artifact_xml
    ) {
        $node = $artifact_xml->addChild('file');
        $node->addAttribute('id',      $this->getFileInfoIdForXML($file_info));
        $node->addChild('filename',    $file_info->getFilename());
        $node->addChild('path',        $this->path_exporter->getPath($file_info));
        $node->addChild('filesize',    $file_info->getFilesize());
        $node->addChild('filetype',    $file_info->getFiletype());
        $node->addChild('description', $file_info->getDescription());
    }

    private function getFileInfoIdForXML(Tracker_FileInfo $file_info) {
        return self::ID_PREFIX . $file_info->getId();
    }
}
