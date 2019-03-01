<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1;

use Docman_LockFactory;
use Luracast\Restler\RestException;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\Upload\UploadCreationConflictException;
use Tuleap\Docman\Upload\UploadCreationFileMismatchException;
use Tuleap\Docman\Upload\UploadMaxSizeExceededException;
use Tuleap\Docman\Upload\Version\VersionToUploadCreator;

class DocmanItemUpdator
{
    /**
     * @var ApprovalTableRetriever
     */
    private $approval_table_retriever;
    /**
     * @var Docman_LockFactory
     */
    private $lock_factory;
    /**
     * @var VersionToUploadCreator
     */
    private $creator;
    /**
     * @var FileVersionToUploadVisitorBeforeUpdateValidator
     */
    private $before_update_validator;

    public function __construct(
        ApprovalTableRetriever $approval_table_retriever,
        Docman_LockFactory $lock_factory,
        VersionToUploadCreator $creator,
        FileVersionToUploadVisitorBeforeUpdateValidator $before_update_validator
    ) {
        $this->approval_table_retriever = $approval_table_retriever;
        $this->lock_factory             = $lock_factory;
        $this->creator                  = $creator;
        $this->before_update_validator  = $before_update_validator;
    }

    /**
     * @throws ExceptionDocumentHasApprovalTable
     * @throws ExceptionItemIsLockedByAnotherUser
     * @throws UploadMaxSizeExceededException
     * @throws RestException
     */
    public function update(
        \Docman_Item $item,
        \PFUser $user,
        DocmanFilesPATCHRepresentation $patch_representation,
        \DateTimeImmutable $time
    ) : CreatedItemFilePropertiesRepresentation {
        $approval_table = $this->approval_table_retriever->retrieveByItem($item);
        if ($approval_table) {
            throw new ExceptionDocumentHasApprovalTable();
        }

        $lock_infos = $this->lock_factory->getLockInfoForItem($item);

        if ($lock_infos && (int)$lock_infos['user_id'] !== (int)$user->getId()) {
            throw new ExceptionItemIsLockedByAnotherUser();
        }

        $item->accept($this->before_update_validator, []);

        try {
            $document_to_upload = $this->creator->create(
                $item,
                $user,
                $time,
                $patch_representation->version_title,
                $patch_representation->change_log,
                $patch_representation->file_properties->file_name,
                $patch_representation->file_properties->file_size
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
