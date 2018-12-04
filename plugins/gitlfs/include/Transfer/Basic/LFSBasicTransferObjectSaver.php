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

namespace Tuleap\GitLFS\Transfer\Basic;

use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Tuleap\GitLFS\LFSObject\LFSObject;
use Tuleap\GitLFS\LFSObject\LFSObjectPathAllocator;
use Tuleap\GitLFS\LFSObject\LFSObjectRetriever;
use Tuleap\GitLFS\StreamFilter\StreamFilter;

class LFSBasicTransferObjectSaver
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

    public function __construct(
        FilesystemInterface $filesystem,
        LFSObjectRetriever $lfs_object_retriever,
        LFSObjectPathAllocator $path_allocator
    ) {
        $this->filesystem           = $filesystem;
        $this->lfs_object_retriever = $lfs_object_retriever;
        $this->path_allocator       = $path_allocator;
    }

    /**
     * @throws LFSBasicTransferException
     */
    public function saveObject(LFSObject $lfs_object, $input_resource)
    {
        $ready_to_be_added_path = $this->path_allocator->getPathForReadyToBeAvailableObject($lfs_object);

        if (! $this->doesObjectNeedsToBeSaved($ready_to_be_added_path, $lfs_object)) {
            return;
        }

        if (! \is_resource($input_resource)) {
            throw new \InvalidArgumentException('$input_resource must be a resource, got ' . gettype($input_resource));
        }

        $sha256_processor_filter = new SHA256ComputeOnReadFilter();
        $sha256_filter_handle    = StreamFilter::prependFilter($input_resource, $sha256_processor_filter);
        $max_size_blocker_filter = new BlockToMaxSizeOnReadFilter($lfs_object->getSize());
        $max_size_filter_handle  = StreamFilter::prependFilter($input_resource, $max_size_blocker_filter);

        $temporary_path = $this->path_allocator->getPathForSaveInProgressObject($lfs_object);
        try {
            $this->writeTemporaryObjectFile($temporary_path, $input_resource, $max_size_blocker_filter);
            $this->checkTemporaryObjectFileMatchesExpectations($lfs_object, $max_size_blocker_filter, $sha256_processor_filter);
            $this->markAsReadyToBeAvailable($temporary_path, $ready_to_be_added_path, $lfs_object);
        } finally {
            StreamFilter::removeFilter($sha256_filter_handle);
            StreamFilter::removeFilter($max_size_filter_handle);
            $this->tryToCleanUpFile($temporary_path);
        }
    }

    /**
     * @return bool
     */
    private function doesObjectNeedsToBeSaved($ready_to_be_added_path, LFSObject $lfs_object)
    {
        return ! $this->lfs_object_retriever->doesLFSObjectExists($lfs_object) &&
                ! $this->filesystem->has($ready_to_be_added_path);
    }

    private function writeTemporaryObjectFile($path, $input_resource, BlockToMaxSizeOnReadFilter $max_size_blocker_filter)
    {
        $is_writing_temporary_file_success = $this->filesystem->writeStream($path, $input_resource);
        if ($max_size_blocker_filter->hasMaximumSizeBeenExceeded()) {
            throw new LFSBasicTransferObjectOutOfBoundSizeException();
        }
        if (! $is_writing_temporary_file_success) {
            throw new \RuntimeException('Cannot write LFS object to the path temporary ' . $path);
        }
    }

    private function checkTemporaryObjectFileMatchesExpectations(
        LFSObject $lfs_object,
        BlockToMaxSizeOnReadFilter $max_size_blocker_filter,
        SHA256ComputeOnReadFilter $sha256_processor
    ) {
        if ($max_size_blocker_filter->getReadDataSize() !== $lfs_object->getSize()) {
            throw new LFSBasicTransferObjectSizeException($lfs_object->getSize(), $max_size_blocker_filter->getReadDataSize());
        }

        $oid_temporary_upload = $sha256_processor->getHashValue();
        if ($oid_temporary_upload !== $lfs_object->getOID()->getValue()) {
            throw new LFSBasicTransferObjectIntegrityException($lfs_object->getOID()->getValue(), $oid_temporary_upload);
        }
    }

    private function markAsReadyToBeAvailable($temporary_path, $ready_to_be_added_path, LFSObject $lfs_object)
    {
        try {
            $is_marking_object_as_ready_success = ! $this->doesObjectNeedsToBeSaved($ready_to_be_added_path, $lfs_object) ||
                $this->filesystem->rename($temporary_path, $ready_to_be_added_path);
        } catch (FileExistsException $ex) {
            return;
        }

        if (! $is_marking_object_as_ready_success) {
            $oid_value = $lfs_object->getOID()->getValue();
            throw new \RuntimeException("Cannot mark LFS object $oid_value has ready to be available");
        }
    }

    private function tryToCleanUpFile($path)
    {
        try {
            $this->filesystem->delete($path);
        } catch (FileNotFoundException $ex) {
        }
    }
}
