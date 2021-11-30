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

namespace Tuleap\GitLFS\LFSObject;

use League\Flysystem\FilesystemWriter;
use Tuleap\DB\DBTransactionExecutor;

class LFSObjectRemover
{
    /**
     * @var LFSObjectDAO
     */
    private $dao;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var FilesystemWriter
     */
    private $filesystem;
    /**
     * @var LFSObjectPathAllocator
     */
    private $path_allocator;

    public function __construct(
        LFSObjectDAO $dao,
        DBTransactionExecutor $transaction_executor,
        FilesystemWriter $filesystem,
        LFSObjectPathAllocator $path_allocator,
    ) {
        $this->dao                  = $dao;
        $this->transaction_executor = $transaction_executor;
        $this->filesystem           = $filesystem;
        $this->path_allocator       = $path_allocator;
    }

    public function removeDanglingObjects(int $deletion_delay): void
    {
        $this->dao->deleteUnusableReferences($deletion_delay);
        $this->transaction_executor->execute(function () {
            $unused_object_rows = $this->dao->searchUnusedObjects();
            foreach ($unused_object_rows as $unused_object_row) {
                $lfs_object    = new LFSObject(
                    new LFSObjectID($unused_object_row['object_oid']),
                    $unused_object_row['object_size']
                );
                $expected_path = $this->path_allocator->getPathForAvailableObject($lfs_object);
                $this->filesystem->delete($expected_path);
                $this->dao->deleteObjectByID($unused_object_row['id']);
            }
        });
    }
}
