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

namespace Tuleap\Docman\Upload\Version;

use Docman_Item;
use Docman_Version;
use Tuleap\Docman\Tus\TusFileInformation;
use Tuleap\Docman\Tus\TusFinisherDataStore;

final class VersionUploadFinisher implements TusFinisherDataStore
{
    /**
     * @var \Logger
     */
    private $logger;
    /**
     * @var VersionUploadPathAllocator
     */
    private $document_upload_path_allocator;
    /**
     * @var \Docman_ItemFactory
     */
    private $docman_item_factory;
    /**
     * @var \Docman_VersionFactory
     */
    private $version_factory;
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var DocumentOnGoingVersionToUploadDAO
     */
    private $version_to_upload_dao;
    /**
     * @var \Docman_ItemDao
     */
    private $docman_item_dao;
    /**
     * @var \Docman_FileStorage
     */
    private $docman_file_storage;
    /**
     * @var \Docman_MIMETypeDetector
     */
    private $docman_mime_type_detector;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var \Docman_LockFactory
     */
    private $lock_factory;

    public function __construct(
        \Logger $logger,
        VersionUploadPathAllocator $document_upload_path_allocator,
        \Docman_ItemFactory $docman_item_factory,
        \Docman_VersionFactory $version_factory,
        \EventManager $event_manager,
        DocumentOnGoingVersionToUploadDAO $version_to_upload_dao,
        \Docman_ItemDao $docman_item_dao,
        \Docman_FileStorage $docman_file_storage,
        \Docman_MIMETypeDetector $docman_mime_type_detector,
        \UserManager $user_manager,
        \Docman_LockFactory $lock_factory
    ) {
        $this->logger                         = $logger;
        $this->document_upload_path_allocator = $document_upload_path_allocator;
        $this->docman_item_factory            = $docman_item_factory;
        $this->version_factory                = $version_factory;
        $this->event_manager                  = $event_manager;
        $this->version_to_upload_dao          = $version_to_upload_dao;
        $this->docman_item_dao                = $docman_item_dao;
        $this->docman_file_storage            = $docman_file_storage;
        $this->docman_mime_type_detector      = $docman_mime_type_detector;
        $this->user_manager                   = $user_manager;
        $this->lock_factory                   = $lock_factory;
    }

    public function finishUpload(TusFileInformation $file_information): void
    {
        $version_id = $file_information->getID();

        $uploaded_document_path   = $this->document_upload_path_allocator->getPathForItemBeingUploaded($version_id);
        $current_value_user_abort = (bool)ignore_user_abort(true);
        try {
            $this->createVersion($uploaded_document_path, $version_id);
        } finally {
            ignore_user_abort($current_value_user_abort);
        }
        \unlink($uploaded_document_path);
        $this->version_to_upload_dao->deleteByVersionID($version_id);
    }

    private function createVersion($uploaded_document_path, $version_id): void
    {
        $this->version_to_upload_dao->wrapAtomicOperations(
            function () use ($uploaded_document_path, $version_id) {
                $item_row = $this->version_to_upload_dao->searchDocumentVersionOngoingUploadByVersionId($version_id);
                if (empty($item_row)) {
                    $this->logger->info("Version #$version_id could not found in the DB to be marked as uploaded");
                    return;
                }

                /**
                 * @var $item Docman_Item|null
                 */
                $item = $this->docman_item_factory->getItemFromDb($item_row['item_id']);
                if (! $item) {
                    $this->logger->info("Item #" . $item_row['item_id'] . " could not found in the DB to be marked as uploaded");
                    return;
                }

                /**
                 * @var Docman_Version|null
                 */
                $next_version_id = $this->version_factory->getNextVersionNumber($item);


                /*
                 * Some tables of the docman plugin relies on the MyISAM engine so the DB transaction
                 * will not be taken into account. The copy of the file being the most brittle operation
                 * we want to do it first before inserting anything in the DB to limit to a maximum incorrect
                 * states.
                 */
                $file_path = $this->docman_file_storage->copy(
                    $uploaded_document_path,
                    $item->getTitle(),
                    $item->getGroupId(),
                    $item->getId(),
                    $next_version_id
                );
                if ($file_path === false) {
                    throw new \RuntimeException('Could not copy uploaded item #' . $item->getId() . "version #" . $version_id);
                }

                $current_time             = (new \DateTimeImmutable)->getTimestamp();
                $has_version_been_created = $this->version_factory->create(
                    [
                        'item_id'   => $item->getId(),
                        'number'    => $next_version_id,
                        'user_id'   => $item_row['user_id'],
                        'label'     => $item_row['version_title'],
                        'changelog' => $item_row['changelog'],
                        'filename'  => $item->getTitle(),
                        'filesize'  => $item_row['filesize'],
                        'filetype'  => $this->getFiletype($file_path),
                        'path'      => $file_path,
                        'date'      => $current_time
                    ]
                );
                if (! $has_version_been_created) {
                    $this->docman_item_dao->delete($version_id);
                    \unlink($file_path);
                    throw new \RuntimeException('Not able to create the first version of item #' . $version_id);
                }

                $current_user = $this->user_manager->getUserById($item_row['user_id']);
                if ($this->lock_factory->getLockInfoForItem($item) !== false) {
                    $this->lock_factory->lock($item, $current_user);
                    $this->triggerLockEvents($item, $current_user);
                }

                $params = [
                    'item'     => $item,
                    'user'     => $current_user,
                    'group_id' => $item->getGroupId(),
                    'parent'   => $this->docman_item_factory->getItemFromDb($item->getParentId())
                ];
                $this->triggerPostUpdateEvents($params);
            }
        );

        $this->logger->debug('Version #' . $version_id . ' has been created');
    }

    private function getFiletype($path): string
    {
        $filename = basename($path);
        if ($this->docman_mime_type_detector->isAnOfficeFile($filename)) {
            return $this->docman_mime_type_detector->getRightOfficeType($filename);
        }
        return mime_content_type($path);
    }

    private function triggerPostUpdateEvents(array $params): void
    {
        $this->event_manager->processEvent('plugin_docman_event_add', $params);
        $this->event_manager->processEvent('send_notifications', []);
    }

    private function triggerLockEvents(Docman_Item $item, \PFUser $user): void
    {
        $params = [
            'group_id' => $item->getGroupId(),
            'item'     => $item,
            'user'     => $user
        ];
        $this->event_manager->processEvent('plugin_docman_event_lock_add', $params);
    }
}
