<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Docman\REST\v1\Files;

use Luracast\Restler\RestException;
use Tuleap\Docman\REST\v1\ExceptionItemIsLockedByAnotherUser;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetadataObsolescenceDateRetriever;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\Upload\UploadCreationConflictException;
use Tuleap\Docman\Upload\UploadCreationFileMismatchException;
use Tuleap\Docman\Upload\UploadMaxSizeExceededException;
use Tuleap\Docman\Upload\Version\VersionToUploadCreator;

class DocmanItemFileUpdator
{
    /**
     * @var VersionToUploadCreator
     */
    private $creator;
    /**
     * @var ItemStatusMapper
     */
    private $status_mapper;
    /**
     * @var HardcodedMetadataObsolescenceDateRetriever
     */
    private $date_retriever;
    /**
     * @var \Docman_PermissionsManager
     */
    private $docman_permissions_manager;

    public function __construct(
        VersionToUploadCreator $creator,
        ItemStatusMapper $status_mapper,
        HardcodedMetadataObsolescenceDateRetriever $date_retriever,
        \Docman_PermissionsManager $docman_permissions_manager
    ) {
        $this->creator                    = $creator;
        $this->status_mapper              = $status_mapper;
        $this->date_retriever             = $date_retriever;
        $this->docman_permissions_manager = $docman_permissions_manager;
    }

    /**
     * @return CreatedItemFilePropertiesRepresentation
     * @throws RestException
     * @throws \Tuleap\Docman\REST\v1\ExceptionItemIsLockedByAnotherUser
     * @throws \Tuleap\Docman\REST\v1\Metadata\HardCodedMetadataException
     */
    public function updateFile(
        \Docman_Item $item,
        \PFUser $user,
        DocmanFilesPATCHRepresentation $patch_representation,
        \DateTimeImmutable $current_time
    ): CreatedItemFilePropertiesRepresentation {
        if ($this->docman_permissions_manager->_itemIsLockedForUser($user, (int)$item->getId())) {
            throw new ExceptionItemIsLockedByAnotherUser();
        }

        $status_id = $this->status_mapper->getItemStatusIdFromItemStatusString(
            $patch_representation->status
        );

        $obsolescence_date_time_stamp = $obsolescence_date_time_stamp = $this->date_retriever->getTimeStampOfDate(
            $patch_representation->obsolescence_date,
            $current_time
        );

        try {
            $document_to_upload = $this->creator->create(
                $item,
                $user,
                $current_time,
                $patch_representation->version_title,
                $patch_representation->change_log,
                $patch_representation->file_properties->file_name,
                $patch_representation->file_properties->file_size,
                $patch_representation->should_lock_file,
                $status_id,
                $obsolescence_date_time_stamp,
                $patch_representation->title,
                $patch_representation->description,
                $patch_representation->approval_table_action
            );
        } catch (UploadCreationConflictException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (UploadCreationFileMismatchException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (UploadMaxSizeExceededException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $file_properties_representation = new CreatedItemFilePropertiesRepresentation();
        $file_properties_representation->build($document_to_upload->getUploadHref());

        return $file_properties_representation;
    }
}
