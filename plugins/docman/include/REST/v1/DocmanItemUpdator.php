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
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\Upload\DocumentToUploadMaxSizeExceededException;

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

    public function __construct(ApprovalTableRetriever $approval_table_retriever, Docman_LockFactory $lock_factory)
    {
        $this->approval_table_retriever = $approval_table_retriever;
        $this->lock_factory             = $lock_factory;
    }

    /**
     * @throws ExceptionDocumentHasApprovalTable
     * @throws ExceptionItemIsLockedByAnotherUser
     * @throws DocumentToUploadMaxSizeExceededException
     */
    public function update(\Docman_Item $item, \PFUser $user, DocmanItemPATCHRepresentation $patch_representation) : void
    {
        $approval_table = $this->approval_table_retriever->retrieveByItem($item);
        if ($approval_table) {
            throw new ExceptionDocumentHasApprovalTable();
        }

        $lock_infos = $this->lock_factory->getLockInfoForItem($item);

        if ($lock_infos && (int)$lock_infos['user_id'] !== $user->getId()) {
            throw new ExceptionItemIsLockedByAnotherUser();
        }

        if ($patch_representation->file_properties) {
            $file_size = $patch_representation->file_properties->file_size;
            if ((int)$file_size > (int) \ForgeConfig::get('sys_max_size_upload')) {
                throw new DocumentToUploadMaxSizeExceededException(
                    (int) $file_size,
                    (int) \ForgeConfig::get('sys_max_size_upload')
                );
            }
        }
    }
}
