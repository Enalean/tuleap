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

use Backend;
use ForgeConfig;
use PFUser;
use Tracker_Artifact_ChangesetValue_File;
use Tracker_FormElement_Field_File;
use Tracker_FormElement_Field_Value_FileDao;

class ChangesetValueFileSaver
{
    /**
     * @var Tracker_FormElement_Field_Value_FileDao
     */
    private $dao;
    /**
     * @var AttachmentCreator
     */
    private $attachment_creator;

    public function __construct(
        Tracker_FormElement_Field_Value_FileDao $dao,
        AttachmentCreator $attachment_creator
    ) {
        $this->dao                = $dao;
        $this->attachment_creator = $attachment_creator;
    }

    public function saveValue(
        PFUser $current_user,
        Tracker_FormElement_Field_File $field,
        int $changeset_value_id,
        array $value,
        ?Tracker_Artifact_ChangesetValue_File $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping
    ): bool {
        $save_ok = true;

        if ($previous_changesetvalue) {
            $save_ok = $this->saveFilesFromPreviousChangeset($changeset_value_id, $value, $previous_changesetvalue);
        }

        $this->initFolder($field);

        $fileinfo_ids = [];
        foreach ($value as $i => $submitted_value_info) {
            if ((string) $i === 'delete') {
                continue;
            }

            $attachment = $this->attachment_creator->createAttachment(
                $current_user,
                $field,
                $submitted_value_info,
                $url_mapping
            );
            if ($attachment) {
                $fileinfo_ids[] = $attachment->getId();
            }
        }

        if (count($fileinfo_ids)) {
            $save_ok = $save_ok && $this->dao->create($changeset_value_id, $fileinfo_ids);
        }

        return $save_ok;
    }

    private function saveFilesFromPreviousChangeset(
        int $changeset_value_id,
        array $value,
        Tracker_Artifact_ChangesetValue_File $previous_changesetvalue
    ): bool {
        $previous_fileinfo_ids = [];
        foreach ($previous_changesetvalue as $previous_attachment) {
            if (empty($value['delete']) || ! in_array($previous_attachment->getId(), $value['delete'])) {
                $previous_fileinfo_ids[] = $previous_attachment->getId();
            } else {
                if (! empty($value['delete']) && in_array($previous_attachment->getId(), $value['delete'])) {
                    $previous_attachment->deleteFiles();
                }
            }
        }
        if (count($previous_fileinfo_ids)) {
            return $this->dao->create($changeset_value_id, $previous_fileinfo_ids);
        }

        return true;
    }

    protected function initFolder(Tracker_FormElement_Field_File $field): void
    {
        $backend                  = Backend::instance();
        $thumbnail_path           = $field->getRootPath() . '/thumbnails';
        $no_filter_file_extension = [];

        if (! is_dir($thumbnail_path) && ! mkdir($thumbnail_path, 0750, true) && ! is_dir($thumbnail_path)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $thumbnail_path));
        }

        $backend->recurseChownChgrp(
            $field->getRootPath(),
            ForgeConfig::get('sys_http_user'),
            ForgeConfig::get('sys_http_user'),
            $no_filter_file_extension
        );
    }
}
