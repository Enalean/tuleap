<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Docman\Upload;

use Tuleap\Docman\Tus\TusEvent;
use Tuleap\Docman\Tus\TusEventSubscriber;

final class DocumentUploaded implements TusEventSubscriber
{
    /**
     * @var \Logger
     */
    private $logger;
    /**
     * @var DocumentUploadPathAllocator
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
     * @var \PermissionsManager
     */
    private $permission_manager;
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var DocumentOngoingUploadDAO
     */
    private $document_ongoing_upload_dao;
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

    public function __construct(
        \Logger $logger,
        DocumentUploadPathAllocator $document_upload_path_allocator,
        \Docman_ItemFactory $docman_item_factory,
        \Docman_VersionFactory $version_factory,
        \PermissionsManager $permission_manager,
        \EventManager $event_manager,
        DocumentOngoingUploadDAO $document_ongoing_upload_dao,
        \Docman_FileStorage $docman_file_storage,
        \Docman_MIMETypeDetector $docman_mime_type_detector,
        \UserManager $user_manager
    ) {
        $this->logger                         = $logger;
        $this->document_upload_path_allocator = $document_upload_path_allocator;
        $this->docman_item_factory            = $docman_item_factory;
        $this->version_factory                = $version_factory;
        $this->permission_manager             = $permission_manager;
        $this->event_manager                  = $event_manager;
        $this->document_ongoing_upload_dao    = $document_ongoing_upload_dao;
        $this->docman_file_storage            = $docman_file_storage;
        $this->docman_mime_type_detector      = $docman_mime_type_detector;
        $this->user_manager                   = $user_manager;
    }

    /**
     * @return string
     */
    public function getInterestedBySubject()
    {
        return TusEvent::UPLOAD_COMPLETED;
    }

    /**
     * @return void
     */
    public function notify(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $item_id = $request->getAttribute('item_id');
        if ($item_id === null) {
            throw new \LogicException('The item ID was not given in the request, not able to save the uploaded document');
        }

        $uploaded_document_path = $this->document_upload_path_allocator->getPathForItemBeingUploaded($item_id);
        $this->createDocument($uploaded_document_path, $item_id);
        \unlink($uploaded_document_path);
    }

    private function createDocument($uploaded_document_path, $item_id)
    {
        $this->document_ongoing_upload_dao->wrapAtomicOperations(function () use ($uploaded_document_path, $item_id) {
            if ($this->docman_item_factory->getItemFromDb($item_id) !== null) {
                $this->logger->warn("Item #$item_id was already marked as uploaded");
                return;
            }

            $document_row = $this->document_ongoing_upload_dao->searchDocumentOngoingUploadByItemID($item_id);

            if (empty($document_row)) {
                $this->logger->info("Item #$item_id could not found in the DB to be marked as uploaded");
                return;
            }

            $current_time    = (new \DateTimeImmutable)->getTimestamp();
            $is_item_created = $this->docman_item_factory->create(
                [
                    'item_id'     => $item_id,
                    'title'       => $document_row['title'],
                    'description' => $document_row['description'],
                    'parent_id'   => $document_row['parent_id'],
                    'group_id'    => $document_row['group_id'],
                    'create_date' => $current_time,
                    'update_date' => $current_time,
                    'user_id'     => $document_row['user_id'],
                    'status'      => PLUGIN_DOCMAN_ITEM_STATUS_NONE,
                    'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_FILE
                ],
                null
            );
            if ($is_item_created === false) {
                throw new \RuntimeException("Not able to create item #$item_id in DB");
            }
            $has_permissions_been_set = $this->permission_manager->clonePermissions(
                $document_row['parent_id'],
                $document_row['item_id'],
                ['PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE']
            );
            if (! $has_permissions_been_set) {
                throw new \RuntimeException('Could not set permissions on item #' . $item_id);
            }

            $file_path = $this->docman_file_storage->copy(
                $uploaded_document_path,
                $document_row['filename'],
                $document_row['group_id'],
                $item_id,
                1
            );
            if ($file_path === false) {
                throw new \RuntimeException('Could not copy uploaded item #' . $item_id);
            }

            $has_version_been_created = $this->version_factory->create([
                'item_id'   => $item_id,
                'number'    => 1,
                'user_id'   => $document_row['user_id'],
                'filename'  => $document_row['filename'],
                'filesize'  => $document_row['filesize'],
                'filetype'  => $this->getFiletype($file_path),
                'path'      => $file_path,
                'date'      => $current_time
            ]);
            if (! $has_version_been_created) {
                \unlink($file_path);
                throw new \RuntimeException('Not able to create the first version of item #' . $item_id);
            }
        });

        $this->notifyCreation($item_id);
        $this->logger->debug('Item #' . $item_id . ' has been created');
    }

    /**
     * @return string
     */
    private function getFiletype($path)
    {
        $filename = basename($path);
        if ($this->docman_mime_type_detector->isAnOfficeFile($filename)) {
            return $this->docman_mime_type_detector->getRightOfficeType($filename);
        }
        return mime_content_type($path);
    }

    private function notifyCreation($item_id)
    {
        $item = $this->docman_item_factory->getItemFromDb($item_id);
        if ($item === null) {
            throw new \LogicException('Document manager item should have been created');
        }

        $user = $this->user_manager->getUserById($item->getOwnerId());

        $this->event_manager->processEvent(
            'plugin_docman_event_add',
            [
                'group_id' => $item->getGroupId(),
                'parent'   => $this->docman_item_factory->getItemFromDb($item->getParentId()),
                'item'     => $item,
                'user'     => $user
            ]
        );
        $this->event_manager->processEvent('send_notifications', []);
        $this->event_manager->processEvent(
            'plugin_docman_event_new_version',
            [
                'group_id' => $item->getGroupId(),
                'item'     => $item,
                'version'  => 1,
                'user'     => $user
            ]
        );
    }
}
