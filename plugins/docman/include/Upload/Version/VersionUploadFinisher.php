<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\Upload\Version;

use Docman_LockFactory;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdateActionChecker;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdater;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\PostUpdate\PostUpdateFileHandler;
use Tuleap\Tus\TusFileInformation;
use Tuleap\Tus\TusFinisherDataStore;
use Tuleap\Upload\UploadPathAllocator;

final class VersionUploadFinisher implements TusFinisherDataStore
{
    /**
     * @var Docman_LockFactory
     */
    private $lock_factory;
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
     * @var DocumentOnGoingVersionToUploadDAO
     */
    private $version_to_upload_dao;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
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
     * @var ApprovalTableUpdater
     */
    private $approval_table_updater;
    /**
     * @var ApprovalTableRetriever
     */
    private $approval_table_retriever;
    /**
     * @var ApprovalTableUpdateActionChecker
     */
    private $approval_table_action_checker;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        UploadPathAllocator $document_upload_path_allocator,
        \Docman_ItemFactory $docman_item_factory,
        \Docman_VersionFactory $version_factory,
        DocumentOnGoingVersionToUploadDAO $version_to_upload_dao,
        DBTransactionExecutor $transaction_executor,
        \Docman_FileStorage $docman_file_storage,
        \Docman_MIMETypeDetector $docman_mime_type_detector,
        \UserManager $user_manager,
        Docman_LockFactory $lock_factory,
        ApprovalTableUpdater $approval_table_updater,
        ApprovalTableRetriever $approval_table_retriever,
        ApprovalTableUpdateActionChecker $approval_table_action_checker,
        private PostUpdateFileHandler $post_update_file_handler,
    ) {
        $this->logger                         = $logger;
        $this->document_upload_path_allocator = $document_upload_path_allocator;
        $this->docman_item_factory            = $docman_item_factory;
        $this->version_factory                = $version_factory;
        $this->version_to_upload_dao          = $version_to_upload_dao;
        $this->transaction_executor           = $transaction_executor;
        $this->docman_file_storage            = $docman_file_storage;
        $this->docman_mime_type_detector      = $docman_mime_type_detector;
        $this->user_manager                   = $user_manager;
        $this->lock_factory                   = $lock_factory;
        $this->approval_table_updater         = $approval_table_updater;
        $this->approval_table_retriever       = $approval_table_retriever;
        $this->approval_table_action_checker  = $approval_table_action_checker;
    }

    public function finishUpload(ServerRequestInterface $request, TusFileInformation $file_information): void
    {
        $upload_id = $file_information->getID();

        $uploaded_document_path   = $this->document_upload_path_allocator->getPathForItemBeingUploaded($file_information);
        $current_value_user_abort = (bool) ignore_user_abort(true);
        try {
            $this->createVersion($uploaded_document_path, $upload_id);
        } finally {
            ignore_user_abort($current_value_user_abort);
        }
        \unlink($uploaded_document_path);
        $this->version_to_upload_dao->deleteByVersionID($upload_id);
    }

    private function createVersion(string $uploaded_document_path, int $upload_id): void
    {
        $this->transaction_executor->execute(
            function () use ($uploaded_document_path, $upload_id) {
                $upload_row = $this->version_to_upload_dao->searchDocumentVersionOngoingUploadByUploadID($upload_id);
                if (empty($upload_row)) {
                    $this->logger->info("Upload #$upload_id could not found in the DB to be marked as uploaded");
                    return;
                }

                $item = $this->docman_item_factory->getItemFromDb($upload_row['item_id']);
                if ($item === null) {
                    $this->logger->info('Item #' . $upload_row['item_id'] . ' could not found in the DB to add a new version');
                    return;
                }
                if (
                    ! $item->accept(new DoesItemHasExpectedTypeVisitor(\Docman_File::class)) &&
                    ! $item->accept(new DoesItemHasExpectedTypeVisitor(\Docman_Empty::class))
                ) {
                    throw new \LogicException(sprintf('Item #%d does not have the expected type %s or %s', $upload_row['item_id'], \Docman_File::class, \Docman_Empty::class));
                }
                assert($item instanceof \Docman_File || $item instanceof \Docman_Empty);

                $next_version_id = (int) $this->version_factory->getNextVersionNumber($item);
                $item_id         = (int) $item->getId();

                /*
                 * Some tables of the docman plugin relies on the MyISAM engine so the DB transaction
                 * will not be taken into account. The copy of the file being the most brittle operation
                 * we want to do it first before inserting anything in the DB to limit to a maximum incorrect
                 * states.
                 */
                $file_path = $this->docman_file_storage->copy(
                    $uploaded_document_path,
                    $upload_row['title'],
                    $item->getGroupId(),
                    $item_id,
                    $next_version_id
                );
                if ($file_path === false) {
                    throw new \RuntimeException('Could not copy uploaded file for item #' . $item->getId() . ' of upload #' . $upload_id);
                }

                $current_time             = (new \DateTimeImmutable())->getTimestamp();
                $has_version_been_created = $this->version_factory->create(
                    [
                        'item_id'   => $item_id,
                        'number'    => $next_version_id,
                        'user_id'   => $upload_row['user_id'],
                        'label'     => $upload_row['version_title'],
                        'changelog' => $upload_row['changelog'],
                        'filename'  => $upload_row['filename'],
                        'filesize'  => $upload_row['filesize'],
                        'filetype'  => $this->getFiletype($upload_row['filename'], $file_path),
                        'path'      => $file_path,
                        'date'      => $current_time,
                    ]
                );

                $current_user = $this->user_manager->getUserById($upload_row['user_id']);
                if ($current_user === null) {
                    throw new \RuntimeException('Can not find user ID #' . $upload_row['user_id']);
                }

                if ((bool) $upload_row['is_file_locked']) {
                    $this->lock_factory->lock($item, $current_user);
                } else {
                    $this->lock_factory->unlock($item, $current_user);
                }

                if (! $has_version_been_created) {
                    \unlink($file_path);
                    throw new \RuntimeException("Not able to create a new version for item #$item_id from upload #$upload_id");
                }

                $last_update_date_change = $this->docman_item_factory->update(
                    [
                        'id'                => $item_id,
                        'title'             => $upload_row['title'],
                        'description'       => $upload_row['description'],
                        'obsolescence_date' => $upload_row['obsolescence_date'],
                        'status'            => $upload_row['status'],
                        'item_type'         => PLUGIN_DOCMAN_ITEM_TYPE_FILE,
                    ]
                );
                if (! $last_update_date_change) {
                    \unlink($file_path);
                    $this->version_factory->deleteSpecificVersion($item, $next_version_id);
                    throw new \RuntimeException("Not able to update last update date for item #$item_id from upload #$upload_id");
                }

                $approval_table_action = $upload_row['approval_table_action'];
                if (
                    $this->approval_table_retriever->hasApprovalTable($item)
                    && $this->approval_table_action_checker->checkAvailableUpdateAction($approval_table_action)
                ) {
                    $item_current_version = $this->version_factory->getCurrentVersionForItem($item);
                    $item->setCurrentVersion($item_current_version);
                    $this->approval_table_updater->updateApprovalTable($item, $current_user, $approval_table_action);
                }
                $this->post_update_file_handler->triggerPostUpdateEvents($item, $current_user);
            }
        );

        $this->logger->debug('New version from upload #' . $upload_id . ' has been created');
    }

    private function getFiletype(string $filename, string $path): string
    {
        $mime_type = $this->docman_mime_type_detector->getRightOfficeType($filename);
        if ($mime_type !== null) {
            return $mime_type;
        }
        return mime_content_type($path);
    }
}
