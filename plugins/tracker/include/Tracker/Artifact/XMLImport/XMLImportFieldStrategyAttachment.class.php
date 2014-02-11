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

class Tracker_Artifact_XMLImport_XMLImportFieldStrategyAttachment implements Tracker_Artifact_XMLImport_XMLImportFieldStrategy {

    /** @var string */
    private $extraction_path;

    /** @var Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact */
    private $files_importer;

    public function __construct($extraction_path, Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact $files_importer) {
        $this->extraction_path = $extraction_path;
        $this->files_importer   = $files_importer;
    }

    /**
     * Extract Field data from XML input
     *
     * @param SimpleXMLElement $field_change
     * @param SimpleXMLElement $xml_artifact
     *
     * @return mixed
     */
    public function getFieldData(SimpleXMLElement $field_change) {
        $values      = $field_change->value;
        $files_infos = array();

        foreach ($values as $value) {
            $file = $this->files_importer->getFileXML((string) $value);

            if (! $this->files_importer->fileIsAlreadyImported((string) $file->id)) {
                $files_infos[] = $this->getFileInfoForAttachment($file);
                $this->files_importer->markAsImported((string) $file->id);
            }
        }
        return $files_infos;
    }

    private function getFileInfoForAttachment(SimpleXMLElement $file_xml) {
        return array(
            'is_migrated' => true,
            'name'        => (string) $file_xml->filename,
            'type'        => (string) $file_xml->filetype,
            'description' => (string) $file_xml->description,
            'size'        => (int) $file_xml->filesize,
            'tmp_name'    => $this->extraction_path .'/'. (string) $file_xml->path,
            'error'       => UPLOAD_ERR_OK,
        );
    }
}