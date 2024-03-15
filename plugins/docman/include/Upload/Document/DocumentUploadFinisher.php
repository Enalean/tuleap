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

namespace Tuleap\Docman\Upload\Document;

use Psr\Http\Message\ServerRequestInterface;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\PostUpdate\PostUpdateFileHandler;
use Tuleap\Tus\TusFileInformation;
use Tuleap\Tus\TusFinisherDataStore;
use Tuleap\Upload\UploadPathAllocator;

final class DocumentUploadFinisher implements TusFinisherDataStore
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var UploadPathAllocator
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
     * @var DocumentOngoingUploadDAO
     */
    private $document_ongoing_upload_dao;
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
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        UploadPathAllocator $document_upload_path_allocator,
        \Docman_ItemFactory $docman_item_factory,
        \Docman_VersionFactory $version_factory,
        \EventManager $event_manager,
        DocumentOngoingUploadDAO $document_ongoing_upload_dao,
        \Docman_ItemDao $docman_item_dao,
        \Docman_FileStorage $docman_file_storage,
        \Docman_MIMETypeDetector $docman_mime_type_detector,
        \UserManager $user_manager,
        DBTransactionExecutor $transaction_executor,
        private PostUpdateFileHandler $post_update_file_handler,
    ) {
        $this->logger                         = $logger;
        $this->document_upload_path_allocator = $document_upload_path_allocator;
        $this->docman_item_factory            = $docman_item_factory;
        $this->version_factory                = $version_factory;
        $this->event_manager                  = $event_manager;
        $this->document_ongoing_upload_dao    = $document_ongoing_upload_dao;
        $this->docman_item_dao                = $docman_item_dao;
        $this->docman_file_storage            = $docman_file_storage;
        $this->docman_mime_type_detector      = $docman_mime_type_detector;
        $this->user_manager                   = $user_manager;
        $this->transaction_executor           = $transaction_executor;
    }

    public function finishUpload(ServerRequestInterface $request, TusFileInformation $file_information): void
    {
        $this->finishUploadEmptyFile($file_information);
    }

    public function finishUploadEmptyFile(TusFileInformation $file_information): void
    {
        $item_id = $file_information->getID();

        $uploaded_document_path   = $this->document_upload_path_allocator->getPathForItemBeingUploaded($file_information);
        $current_value_user_abort = (bool) ignore_user_abort(true);
        try {
            $this->createDocument($uploaded_document_path, $item_id);
        } finally {
            ignore_user_abort($current_value_user_abort);
        }
        \unlink($uploaded_document_path);
        $this->document_ongoing_upload_dao->deleteByItemID($item_id);
    }

    private function createDocument($uploaded_document_path, $item_id): void
    {
        $this->transaction_executor->execute(function () use ($uploaded_document_path, $item_id) {
            if ($this->docman_item_factory->getItemFromDb($item_id) !== null) {
                $this->logger->warning("Item #$item_id was already marked as uploaded");
                return;
            }

            $document_row = $this->document_ongoing_upload_dao->searchDocumentOngoingUploadByItemID($item_id);

            if (empty($document_row)) {
                $this->logger->info("Item #$item_id could not found in the DB to be marked as uploaded");
                return;
            }

            /*
             * Some tables of the docman plugin relies on the MyISAM engine so the DB transaction
             * will not be taken into account. The copy of the file being the most brittle operation
             * we want to do it first before inserting anything in the DB to limit to a maximum incorrect
             * states.
             */
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

            $current_time    = (new \DateTimeImmutable())->getTimestamp();
            $is_item_created = $this->docman_item_factory->create(
                [
                    'item_id'           => $item_id,
                    'title'             => $document_row['title'],
                    'description'       => $document_row['description'],
                    'parent_id'         => $document_row['parent_id'],
                    'group_id'          => $document_row['group_id'],
                    'create_date'       => $current_time,
                    'update_date'       => $current_time,
                    'user_id'           => $document_row['user_id'],
                    'status'            => $document_row['status'],
                    'obsolescence_date' => $document_row['obsolescence_date'],
                    'item_type'         => PLUGIN_DOCMAN_ITEM_TYPE_FILE,
                ],
                null
            );
            if ($is_item_created === false) {
                \unlink($file_path);
                throw new \RuntimeException("Not able to create item #$item_id in DB");
            }

            $has_version_been_created = $this->version_factory->create([
                'item_id'   => $item_id,
                'number'    => 1,
                'user_id'   => $document_row['user_id'],
                'filename'  => $document_row['filename'],
                'filesize'  => $document_row['filesize'],
                'filetype'  => $this->getFiletype($document_row['filename'], $file_path),
                'path'      => $file_path,
                'date'      => $current_time,
            ]);
            if (! $has_version_been_created) {
                $this->docman_item_dao->delete($item_id);
                \unlink($file_path);
                throw new \RuntimeException('Not able to create the first version of item #' . $item_id);
            }
        });

        $this->notifyCreation($item_id);
        $this->logger->debug('Item #' . $item_id . ' has been created');
    }

    private function getFiletype(string $filename, string $path): string
    {
        $mime_type = $this->docman_mime_type_detector->getRightOfficeType($filename);
        if ($mime_type !== null) {
            return $mime_type;
        }
        return mime_content_type($path);
    }

    private function notifyCreation($item_id): void
    {
        $item = $this->docman_item_factory->getItemFromDb($item_id);
        if ($item === null) {
            throw new \LogicException('Document manager item should have been created');
        }
        if (! $item->accept(new DoesItemHasExpectedTypeVisitor(\Docman_File::class))) {
            throw new \LogicException(sprintf('Item #%d does not have the expected type %s', $item_id, \Docman_File::class));
        }
        assert($item instanceof \Docman_File);

        $user_id = $item->getOwnerId();
        $user    = $this->user_manager->getUserById($user_id);
        if ($user === null) {
            throw new \LogicException('Cannot find user #' . $user_id . ' which is the owner of the new document');
        }

        $this->event_manager->processEvent(
            'plugin_docman_event_add',
            [
                'group_id' => $item->getGroupId(),
                'parent' => $this->docman_item_factory->getItemFromDb($item->getParentId()),
                'item' => $item,
                'user' => $user,
            ]
        );
        $this->post_update_file_handler->triggerPostUpdateEvents($item, $user);
    }
}
