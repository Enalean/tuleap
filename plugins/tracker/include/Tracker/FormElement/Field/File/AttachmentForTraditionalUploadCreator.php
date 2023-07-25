<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\File;

use Rule_File;
use Tracker_Artifact_XMLImport_XMLImportFieldStrategyAttachment;
use Tracker_FileInfo;
use Tracker_FormElement_Field_File;
use Tuleap\Tracker\FormElement\Field\File\Upload\FileToDownload;

class AttachmentForTraditionalUploadCreator implements AttachmentCreator
{
    public function __construct(private readonly AttachmentToFinalPlaceMover $mover, private readonly Rule_File $rule_file)
    {
    }

    public function createAttachment(
        \PFUser $current_user,
        Tracker_FormElement_Field_File $field,
        array $submitted_value_info,
        CreatedFileURLMapping $url_mapping,
    ): ?Tracker_FileInfo {
        if (! $this->rule_file->isValid($submitted_value_info)) {
            return null;
        }

        $submitted_by = $current_user;
        if (isset($submitted_value_info['submitted_by'])) {
            $submitted_by = $submitted_value_info['submitted_by'];
        }
        $attachment = new Tracker_FileInfo(
            0,
            $field,
            $submitted_by->getId(),
            trim($submitted_value_info['description']),
            $submitted_value_info['name'],
            $submitted_value_info['size'],
            $submitted_value_info['type']
        );

        if (! $this->save($attachment)) {
            return null;
        }

        if (isset($submitted_value_info['previous_fileinfo_id'])) {
            $url_mapping->add(
                (new FileToDownload((int) $submitted_value_info['previous_fileinfo_id'], $submitted_value_info['name']))->getDownloadHref(),
                (new FileToDownload((int) $attachment->getId(), $submitted_value_info['name']))->getDownloadHref()
            );
        }

        $method   = 'move_uploaded_file';
        $tmp_name = $submitted_value_info['tmp_name'];

        if ($this->isImportOfArtifact($submitted_value_info)) {
            $method = 'copy';
        }

        if ($this->isMoveOfArtifact($submitted_value_info)) {
            $method = 'rename';
        }

        if (! $this->mover->moveAttachmentToFinalPlace($attachment, $method, $tmp_name)) {
            return null;
        }

        return $attachment;
    }

    private function isImportOfArtifact(array $file_value_info): bool
    {
        return isset($file_value_info[Tracker_Artifact_XMLImport_XMLImportFieldStrategyAttachment::FILE_INFO_COPY_OPTION]) &&
            $file_value_info[Tracker_Artifact_XMLImport_XMLImportFieldStrategyAttachment::FILE_INFO_COPY_OPTION];
    }

    private function isMoveOfArtifact(array $file_value_info): bool
    {
        return isset($file_value_info[Tracker_Artifact_XMLImport_XMLImportFieldStrategyAttachment::FILE_INFO_MOVE_OPTION]) &&
            $file_value_info[Tracker_Artifact_XMLImport_XMLImportFieldStrategyAttachment::FILE_INFO_MOVE_OPTION];
    }

    protected function save(Tracker_FileInfo $attachment): bool
    {
        return $attachment->save();
    }
}
