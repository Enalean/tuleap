<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Docman\Upload\Document;

use Docman_PermissionsManager;
use DocmanPlugin;
use PermissionsManager;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Docman\FilenamePattern\FilenameBuilder;
use Tuleap\Docman\Permissions\PermissionItemUpdater;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSet;
use Tuleap\Docman\Upload\UploadCreationConflictException;
use Tuleap\Docman\Upload\UploadCreationFileMismatchException;
use Tuleap\Docman\Upload\UploadMaxSizeExceededException;

class DocumentToUploadCreator
{
    public const EXPIRATION_DELAY_IN_HOURS = 12;

    public function __construct(
        private DocumentOngoingUploadDAO $dao,
        private DBTransactionExecutor $transaction_executor,
        private DocumentMetadataCreator $creator,
        private PermissionsManager $permissions_manager,
        private PermissionItemUpdater $permission_item_updater,
        private FilenameBuilder $filename_builder,
    ) {
    }

    public function create(
        \Docman_Item $parent_item,
        \PFUser $user,
        \DateTimeImmutable $current_time,
        string $title,
        string $description,
        string $filename,
        int $filesize,
        int $status,
        int $obsolescence_date,
        ?array $formatted_metadata,
        ?DocmanItemPermissionsForGroupsSet $permissions_for_groups,
    ): DocumentToUpload {
        if ($filesize > (int) \ForgeConfig::get(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING)) {
            throw new UploadMaxSizeExceededException(
                $filesize,
                (int) \ForgeConfig::get(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING)
            );
        }

        $item_id = $this->transaction_executor->execute(function () use (
            $parent_item,
            $user,
            $current_time,
            $title,
            $description,
            $filename,
            $filesize,
            $status,
            $obsolescence_date,
            $formatted_metadata,
            $permissions_for_groups
        ): int {
            $rows = $this->dao->searchDocumentOngoingUploadByParentIDTitleAndExpirationDate(
                $parent_item->getId(),
                $title,
                $current_time->getTimestamp()
            );
            if (count($rows) > 1) {
                throw new \LogicException(
                    'A identical document is being created multiple times by an ongoing upload, this is not expected'
                );
            }
            if (count($rows) === 1) {
                $row = $rows[0];
                if ($row['user_id'] !== (int) $user->getId()) {
                    throw new UploadCreationConflictException();
                }
                if ($row['filename'] !== $filename || $filesize !== $row['filesize']) {
                    throw new UploadCreationFileMismatchException();
                }
                return $row['item_id'];
            }

            $item_id = $this->dao->saveDocumentOngoingUpload(
                $this->getExpirationDate($current_time)->getTimestamp(),
                (int) $parent_item->getId(),
                $title,
                $description,
                (int) $user->getId(),
                $filename,
                $filesize,
                $status,
                $obsolescence_date
            );

            $updated_filename = $this->filename_builder->buildFilename(
                $filename,
                $parent_item->getGroupId(),
                $title,
                $status,
                '',
                $item_id
            );

            $this->dao->updateDocumentFilenameOngoingUpload($item_id, $updated_filename);

            if ($formatted_metadata) {
                $this->creator->storeItemCustomMetadata($item_id, $formatted_metadata);
            }

            if ($permissions_for_groups === null) {
                $this->permissions_manager->clonePermissions(
                    $parent_item->getId(),
                    $item_id,
                    Docman_PermissionsManager::ITEM_PERMISSION_TYPES
                );
            } else {
                $future_item = new \Docman_Item();
                $future_item->setId($item_id);
                $future_item->setGroupId($parent_item->getGroupId());
                $this->permission_item_updater->initPermissionsOnNewlyCreatedItem(
                    $future_item,
                    $permissions_for_groups->toPermissionsPerUGroupIDAndTypeArray()
                );
            }

            return $item_id;
        });

        return new DocumentToUpload($item_id);
    }

    /**
     * @return \DateTimeImmutable
     */
    private function getExpirationDate(\DateTimeImmutable $current_time)
    {
        return $current_time->add(new \DateInterval('PT' . self::EXPIRATION_DELAY_IN_HOURS . 'H'));
    }
}
