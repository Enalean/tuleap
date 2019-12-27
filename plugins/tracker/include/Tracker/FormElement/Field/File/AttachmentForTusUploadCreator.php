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

class AttachmentForTusUploadCreator implements AttachmentCreator
{
    /**
     * @var AttachmentCreator
     */
    private $next_creator_in_chain;
    /**
     * @var FileOngoingUploadDao
     */
    private $ongoing_upload_dao;
    /**
     * @var FileInfoForTusUploadedFileReadyToBeAttachedProvider
     */
    private $provider;

    public function __construct(
        FileInfoForTusUploadedFileReadyToBeAttachedProvider $provider,
        FileOngoingUploadDao $ongoing_upload_dao,
        AttachmentCreator $next_creator_in_chain
    ) {
        $this->ongoing_upload_dao    = $ongoing_upload_dao;
        $this->next_creator_in_chain = $next_creator_in_chain;
        $this->provider = $provider;
    }

    public function createAttachment(
        PFUser $current_user,
        Tracker_FormElement_Field_File $field,
        array $submitted_value_info,
        CreatedFileURLMapping $url_mapping
    ): ?Tracker_FileInfo {
        if (! isset($submitted_value_info['tus-uploaded-id'])) {
            return $this->next_creator_in_chain->createAttachment($current_user, $field, $submitted_value_info, $url_mapping);
        }

        $id = (int) $submitted_value_info['tus-uploaded-id'];

        $file_information = $this->provider->getFileInfo($id, $current_user, $field);
        if (! $file_information) {
            return null;
        }

        $this->ongoing_upload_dao->deleteUploadedFileThatIsAttached($file_information->getID());

        return $file_information;
    }
}
