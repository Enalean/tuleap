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

namespace Tuleap\Docman\REST\v1\EmbeddedFiles;

use Docman_FileStorage;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Docman\Lock\LockChecker;
use Tuleap\Docman\REST\v1\DocmanItemUpdator;
use Tuleap\Docman\REST\v1\ExceptionItemIsLockedByAnotherUser;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetadataObsolescenceDateRetriever;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetdataObsolescenceDateChecker;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;

class DocmanEmbeddedFileUpdator
{
    /**
     * @var \Docman_FileStorage
     */
    private $file_storage;
    /**
     * @var \Docman_VersionFactory
     */
    private $version_factory;
    /**
     * @var LockChecker
     */
    private $lock_checker;
    /**
     * @var DocmanItemUpdator
     */
    private $updator;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var ItemStatusMapper
     */
    private $status_mapper;
    /**
     * @var HardcodedMetadataObsolescenceDateRetriever
     */
    private $date_retriever;
    /**
     * @var \Docman_ItemFactory
     */
    private $item_factory;

    public function __construct(
        Docman_FileStorage $file_storage,
        \Docman_VersionFactory $version_factory,
        \Docman_ItemFactory $item_factory,
        LockChecker $lock_checker,
        DocmanItemUpdator $updator,
        DBTransactionExecutor $transaction_executor,
        ItemStatusMapper $status_mapper,
        HardcodedMetadataObsolescenceDateRetriever $date_retriever
    ) {
        $this->file_storage              = $file_storage;
        $this->version_factory           = $version_factory;
        $this->lock_checker              = $lock_checker;
        $this->updator                   = $updator;
        $this->transaction_executor      = $transaction_executor;
        $this->status_mapper             = $status_mapper;
        $this->date_retriever            = $date_retriever;
        $this->item_factory              = $item_factory;
    }

    /**
     * @throws ExceptionItemIsLockedByAnotherUser
     * @throws \Tuleap\Docman\REST\v1\Metadata\InvalidDateComparisonException
     * @throws \Tuleap\Docman\REST\v1\Metadata\InvalidDateTimeFormatException
     * @throws \Tuleap\Docman\REST\v1\Metadata\ItemStatusUsageMismatchException
     * @throws \Tuleap\Docman\REST\v1\Metadata\ObsolescenceDateDisabledException
     * @throws \Tuleap\Docman\REST\v1\Metadata\StatusNotFoundBadStatusGivenException
     * @throws \Tuleap\Docman\REST\v1\Metadata\StatusNotFoundNullException
     */
    public function updateEmbeddedFile(
        \Docman_File $item,
        \PFUser $current_user,
        DocmanEmbeddedFilesPATCHRepresentation $representation,
        \DateTimeImmutable $current_time
    ): void {
        $this->lock_checker->checkItemIsLocked($item, $current_user);

        $status_id = $this->status_mapper->getItemStatusIdFromItemStatusString(
            $representation->status
        );

        $obsolescence_date_time_stamp = $this->date_retriever->getTimeStampOfDate(
            $representation->obsolescence_date,
            $current_time
        );
        $this->transaction_executor->execute(
            function () use ($item, $current_user, $representation, $status_id, $obsolescence_date_time_stamp, $current_time) {
                $next_version_id = (int)$this->version_factory->getNextVersionNumber($item);

                $created_file_path = $this->file_storage->store(
                    $representation->embedded_properties->content,
                    $item->getGroupId(),
                    $item->getId(),
                    $next_version_id
                );

                $new_embedded_version_row = [
                    'item_id'   => $item->getId(),
                    'number'    => $next_version_id,
                    'user_id'   => $current_user->getId(),
                    'label'     => $representation->version_title,
                    'changelog' => $representation->change_log,
                    'date'      => $current_time->getTimestamp(),
                    'filename'  => basename($created_file_path),
                    'filesize'  => filesize($created_file_path),
                    'filetype'  => 'text/html',
                    'path'      => $created_file_path
                ];

                $this->version_factory->create($new_embedded_version_row);

                $new_embedded_hardcoded_metadata_row = [
                    'id'                => $item->getId(),
                    'title'             => $representation->title,
                    'description'       => $representation->description,
                    'status'            => $status_id,
                    'obsolescence_date' => $obsolescence_date_time_stamp
                ];

                $this->item_factory->update($new_embedded_hardcoded_metadata_row);

                $this->updator->updateCommonData(
                    $item,
                    $representation->should_lock_file,
                    $current_user,
                    $representation->approval_table_action,
                    $this->version_factory->getCurrentVersionForItem($item)
                );
            }
        );
    }
}
