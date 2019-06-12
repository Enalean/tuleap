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

use PFUser;
use Rule_File;
use Tracker_Artifact_Attachment_TemporaryFileManager;
use Tracker_FileInfo;
use Tracker_FormElement_Field_File;

class AttachmentForRestCreator implements AttachmentCreator
{
    /**
     * @var Tracker_Artifact_Attachment_TemporaryFileManager
     */
    private $temporary_file_manager;
    /**
     * @var AttachmentToFinalPlaceMover
     */
    private $mover;
    /**
     * @var AttachmentCreator
     */
    private $next_creator_in_chain;
    /**
     * @var Rule_File
     */
    private $rule_file;

    public function __construct(
        AttachmentToFinalPlaceMover $mover,
        Tracker_Artifact_Attachment_TemporaryFileManager $temporary_file_manager,
        AttachmentCreator $next_creator_in_chain,
        Rule_File $rule_file
    ) {
        $this->temporary_file_manager = $temporary_file_manager;
        $this->mover                  = $mover;
        $this->next_creator_in_chain  = $next_creator_in_chain;
        $this->rule_file              = $rule_file;
    }

    public function createAttachment(
        PFUser $current_user,
        Tracker_FormElement_Field_File $field,
        array $submitted_value_info,
        CreatedFileURLMapping $url_mapping
    ): ?Tracker_FileInfo {
        if (! $this->rule_file->isValid($submitted_value_info)) {
            return $this->next_creator_in_chain->createAttachment(
                $current_user,
                $field,
                $submitted_value_info,
                $url_mapping
            );
        }

        if (! isset($submitted_value_info['id'])) {
            return $this->next_creator_in_chain->createAttachment(
                $current_user,
                $field,
                $submitted_value_info,
                $url_mapping
            );
        }

        $temporary_file = $this->temporary_file_manager->getFileByTemporaryName($submitted_value_info['id']);
        if (! $temporary_file) {
            return $this->next_creator_in_chain->createAttachment(
                $current_user,
                $field,
                $submitted_value_info,
                $url_mapping
            );
        }

        $attachment = new Tracker_FileInfo(
            $temporary_file->getId(),
            $field,
            $current_user->getId(),
            trim($temporary_file->getDescription()),
            $temporary_file->getName(),
            $temporary_file->getSize(),
            $temporary_file->getType()
        );

        $filename = $submitted_value_info['id'];

        if (! $this->temporary_file_manager->exists($current_user, $filename)) {
            $this->delete($attachment);

            return null;
        }

        $tmp_name = $this->temporary_file_manager->getPath($current_user, $filename);

        $this->temporary_file_manager->removeTemporaryFileInDBByTemporaryName($filename);

        if (! $this->mover->moveAttachmentToFinalPlace($attachment, 'rename', $tmp_name)) {
            return null;
        }

        return $attachment;
    }

    protected function delete(Tracker_FileInfo $attachment): void
    {
        $attachment->delete();
    }
}
