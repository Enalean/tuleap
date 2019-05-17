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
use Tracker_FileInfo;
use Tracker_FormElement_Field_File;
use Tuleap\Tracker\FormElement\Field\File\Upload\FileOngoingUploadDao;
use Tuleap\Tracker\FormElement\Field\File\Upload\Tus\FileBeingUploadedInformationProvider;

class AttachmentForTusUploadCreator implements AttachmentCreator
{
    /**
     * @var AttachmentCreator
     */
    private $next_creator_in_chain;
    /**
     * @var FileBeingUploadedInformationProvider
     */
    private $information_provider;
    /**
     * @var FileOngoingUploadDao
     */
    private $ongoing_upload_dao;

    public function __construct(
        FileBeingUploadedInformationProvider $information_provider,
        FileOngoingUploadDao $ongoing_upload_dao,
        AttachmentCreator $next_creator_in_chain
    ) {
        $this->information_provider  = $information_provider;
        $this->ongoing_upload_dao    = $ongoing_upload_dao;
        $this->next_creator_in_chain = $next_creator_in_chain;
    }

    public function createAttachment(
        PFUser $current_user,
        Tracker_FormElement_Field_File $field,
        array $submitted_value_info
    ): ?Tracker_FileInfo {
        if (! isset($submitted_value_info['tus-uploaded-id'])) {
            return $this->next_creator_in_chain->createAttachment($current_user, $field, $submitted_value_info);
        }

        $file_information = $this->information_provider->getFileInformationByIdForUser(
            (int) $submitted_value_info['tus-uploaded-id'],
            $current_user
        );
        if (! $file_information) {
            return null;
        }

        if ($file_information->getLength() !== $file_information->getOffset()) {
            return null;
        }

        $row = $this->ongoing_upload_dao->searchFileOngoingUploadById($file_information->getID());
        if (! $row) {
            return null;
        }

        if ((int) $field->getId() !== (int) $row['field_id']) {
            return null;
        }

        $this->ongoing_upload_dao->deleteUploadedFileThatIsAttached($file_information->getID());

        return new Tracker_FileInfo(
            $row['id'],
            $field,
            $row['submitted_by'],
            $row['description'],
            $row['filename'],
            $row['filesize'],
            $row['filetype']
        );
    }
}
