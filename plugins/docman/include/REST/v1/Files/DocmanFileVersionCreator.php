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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);


namespace Tuleap\Docman\REST\v1\Files;

use Luracast\Restler\RestException;
use Tuleap\Docman\Upload\UploadCreationConflictException;
use Tuleap\Docman\Upload\UploadCreationFileMismatchException;
use Tuleap\Docman\Upload\UploadMaxSizeExceededException;
use Tuleap\Docman\Upload\Version\VersionToUploadCreator;

class DocmanFileVersionCreator
{
    /**
     * @var VersionToUploadCreator
     */
    private $creator;
    /**
     * @var \Docman_LockFactory
     */
    private $lock_factory;

    public function __construct(VersionToUploadCreator $creator, \Docman_LockFactory $lock_factory)
    {
        $this->creator      = $creator;
        $this->lock_factory = $lock_factory;
    }

    /**
     * @throws RestException
     */
    public function createFileVersion(
        \Docman_Item $item,
        \PFUser $user,
        DocmanFileVersionPOSTRepresentation $representation,
        \DateTimeImmutable $current_time,
        int $status,
        int $obsolesence_date
    ): CreatedItemFilePropertiesRepresentation {
        try {
            $document_to_upload = $this->creator->create(
                $item,
                $user,
                $current_time,
                $representation->version_title,
                $representation->change_log,
                $representation->file_properties->file_name,
                $representation->file_properties->file_size,
                $representation->should_lock_file,
                $status,
                $obsolesence_date,
                $item->getTitle(),
                $item->getDescription(),
                $representation->approval_table_action
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

    /**
     * @throws RestException
     */
    public function createVersionFromEmpty(
        \Docman_Empty $item,
        \PFUser $user,
        FilePropertiesPOSTPATCHRepresentation $representation,
        \DateTimeImmutable $current_time,
        int $status,
        int $obsolesence_date
    ): CreatedItemFilePropertiesRepresentation {
        $is_item_locked = $this->lock_factory->itemIsLocked($item);
        try {
            $document_to_upload = $this->creator->create(
                $item,
                $user,
                $current_time,
                '',
                'Initial version',
                $representation->file_name,
                $representation->file_size,
                $is_item_locked,
                $status,
                $obsolesence_date,
                $item->getTitle(),
                $item->getDescription(),
                null
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
