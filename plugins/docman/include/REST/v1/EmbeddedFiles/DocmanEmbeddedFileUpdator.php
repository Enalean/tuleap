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

    public function __construct(
        Docman_FileStorage $file_storage,
        \Docman_VersionFactory $version_factory,
        LockChecker $lock_checker,
        DocmanItemUpdator $updator,
        DBTransactionExecutor $transaction_executor
    ) {
        $this->file_storage            = $file_storage;
        $this->version_factory         = $version_factory;
        $this->lock_checker            = $lock_checker;
        $this->updator                 = $updator;
        $this->transaction_executor = $transaction_executor;
    }

    /**
     * @throws ExceptionItemIsLockedByAnotherUser
     */
    public function updateEmbeddedFile(
        \Docman_Item $item,
        \PFUser $current_user,
        DocmanEmbeddedFilesPATCHRepresentation $representation
    ): void {
        $this->lock_checker->checkItemIsLocked($item, $current_user);

        $this->transaction_executor->execute(
            function () use ($item, $current_user, $representation) {
                $next_version_id = (int)$this->version_factory->getNextVersionNumber($item);

                $created_file_path = $this->file_storage->store(
                    $representation->embedded_properties->content,
                    $item->getGroupId(),
                    $item->getId(),
                    $next_version_id
                );

                $date = new \DateTimeImmutable();

                $new_embedded_version_row = [
                    'item_id'   => $item->getId(),
                    'number'    => $next_version_id,
                    'user_id'   => $current_user->getId(),
                    'label'     => '',
                    'changelog' => $representation->change_log,
                    'date'      => $date->getTimestamp(),
                    'filename'  => basename($created_file_path),
                    'filesize'  => filesize($created_file_path),
                    'filetype'  => 'text/html',
                    'path'      => $created_file_path
                ];

                $this->version_factory->create($new_embedded_version_row);

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
