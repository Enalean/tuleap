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

namespace Tuleap\GitLFS\Transfer;

use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Tuleap\GitLFS\Object\LFSObject;
use Tuleap\GitLFS\Object\LFSObjectDAO;
use Tuleap\GitLFS\Object\LFSObjectPathAllocator;
use Tuleap\GitLFS\Object\LFSObjectRetriever;

class LFSTransferVerifier
{
    /**
     * @var FilesystemInterface
     */
    private $filesystem;
    /**
     * @var LFSObjectRetriever
     */
    private $lfs_object_retriever;
    /**
     * @var LFSObjectPathAllocator
     */
    private $path_allocator;
    /**
     * @var LFSObjectDAO
     */
    private $lfs_object_dao;

    public function __construct(
        FilesystemInterface $filesystem,
        LFSObjectRetriever $lfs_object_retriever,
        LFSObjectPathAllocator $path_allocator,
        LFSObjectDAO $lfs_object_dao
    ) {
        $this->filesystem           = $filesystem;
        $this->lfs_object_retriever = $lfs_object_retriever;
        $this->path_allocator       = $path_allocator;
        $this->lfs_object_dao       = $lfs_object_dao;
    }

    public function verifyAndMarkLFSObjectAsAvailable(LFSObject $lfs_object, \GitRepository $repository)
    {
        $object_path_ready_to_be_available = $this->path_allocator->getPathForReadyToBeAvailableObject($lfs_object);
        if ($this->lfs_object_retriever->doesLFSObjectExists($lfs_object)) {
            $this->lfs_object_dao->saveObjectReferenceByOIDValue(
                $lfs_object->getOID()->getValue(),
                $repository->getId()
            );
            try {
                $this->filesystem->delete($object_path_ready_to_be_available);
            } catch (FileNotFoundException $exception) {
            }
            return;
        }

        $this->lfs_object_dao->wrapAtomicOperations(function (LFSObjectDAO $dao) use ($lfs_object, $repository) {
            $object_id = $dao->saveObject($lfs_object->getOID()->getValue(), $lfs_object->getSize());
            $dao->saveObjectReference($object_id, $repository->getId());
            try {
                $is_rename_success = $this->filesystem->rename(
                    $this->path_allocator->getPathForReadyToBeAvailableObject($lfs_object),
                    $this->path_allocator->getPathForAvailableObject($lfs_object)
                );
                if (! $is_rename_success) {
                    $oid_value = $lfs_object->getOID()->getValue();
                    throw new \RuntimeException("Cannot move LFS object $oid_value to the available objects");
                }
            } catch (FileNotFoundException $exception) {
                throw new LFSTransferVerificationNotUploadedObjectException($lfs_object);
            }
        });
    }
}
