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

class ArtifactAttachmentXMLZipper implements ArtifactAttachmentXMLExporter
{

    /** @var ArtifactXMLNodeHelper */
    private $node_helper;

    /** @var ZipArchive */
    private $archive;

    /** @var bool */
    private $skip_files = false;

    /** @var ArtifactXMLExporterDao */
    private $dao;

    public function __construct(ArtifactXMLNodeHelper $node_helper, ArtifactXMLExporterDao $dao, ZipArchive $archive, $skip_files)
    {
        $this->node_helper = $node_helper;
        $this->dao         = $dao;
        $this->archive     = $archive;
        $this->skip_files  = $skip_files;
    }

    public function addFilesToArtifact(DOMElement $artifact_node, $artifact_type_id, $artifact_id)
    {
        $dar = $this->dao->searchFilesForArtifact($artifact_id);
        if (count($dar)) {
            $this->archive->addEmptyDir(ArtifactXMLExporter::ARCHIVE_DATA_DIR);
        }
        foreach ($dar as $row) {
            $xml_file_id     = ArtifactAttachmentFieldXMLExporter::XML_FILE_PREFIX . $row['id'];
            $path_in_archive = $this->getFilePathInArchive($xml_file_id);
            if ($this->skip_files) {
                $this->archive->addFromString($path_in_archive, '');
            } else {
                $this->archive->addFile(
                    $this->getFilePathOnServer($artifact_type_id, $row['id']),
                    $path_in_archive
                );
            }
            $file = $this->node_helper->createElement('file');
            $file->setAttribute('id', $xml_file_id);
            $file->appendChild($this->node_helper->getNodeWithValue('filename', $row['filename']));
            $file->appendChild($this->node_helper->getNodeWithValue('path', $this->getFilePathInArchive($xml_file_id)));
            $file->appendChild($this->node_helper->getNodeWithValue('filesize', $row['filesize']));
            $file->appendChild($this->node_helper->getNodeWithValue('filetype', $row['filetype']));
            $file->appendChild($this->node_helper->getNodeWithValue('description', $row['description']));
            $artifact_node->appendChild($file);
        }
    }

    private function getFilePathOnServer($artifact_type_id, $attachment_id)
    {
        return ArtifactFile::getPathOnFilesystemByArtifactTypeId($artifact_type_id, $attachment_id);
    }

    private function getFilePathInArchive($xml_file_id)
    {
        return ArtifactXMLExporter::ARCHIVE_DATA_DIR . DIRECTORY_SEPARATOR . 'Artifact' . $xml_file_id;
    }
}
