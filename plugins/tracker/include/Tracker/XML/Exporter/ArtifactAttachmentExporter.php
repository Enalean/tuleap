<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class Tracker_XML_Exporter_ArtifactAttachmentExporter {

    const DATA_DIR    = 'data';
    const FILE_PREFIX = 'Artifact';

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(Tracker_FormElementFactory $form_element_factory) {
        $this->form_element_factory = $form_element_factory;
    }

    public function exportAttachmentsInArchive(Tracker_Artifact $artifact, ZipArchive $archive) {
        $file_fields    = $this->form_element_factory->getUsedFileFields($artifact->getTracker());
        $last_changeset = $artifact->getLastChangeset();

        if (! $last_changeset) {
            return;
        }

        foreach ($file_fields as $field) {
            $value = $last_changeset->getValue($field);

            if ($value) {
                $this->addFilesIntoArchive($value, $archive);
            }
        }
    }

    private function addFilesIntoArchive(Tracker_Artifact_ChangesetValue_File $value, ZipArchive $archive) {
        $archive->addEmptyDir(self::DATA_DIR);

        foreach ($value->getFiles() as $file_info) {
            if (file_exists($file_info->getPath())) {
                $archive->addFile(
                    $file_info->getPath(),
                    self::DATA_DIR . DIRECTORY_SEPARATOR . self::FILE_PREFIX . $file_info->getId()
                );
            } else {
                $archive->addFromString(self::DATA_DIR . DIRECTORY_SEPARATOR . self::FILE_PREFIX . $file_info->getId(), $file_info->getPath());
            }
        }
    }
}