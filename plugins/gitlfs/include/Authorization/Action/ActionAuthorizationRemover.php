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

namespace Tuleap\GitLFS\Authorization\Action;

use League\Flysystem\FilesystemInterface;
use Tuleap\GitLFS\LFSObject\LFSObjectPathAllocator;

class ActionAuthorizationRemover
{
    /**
     * @var ActionAuthorizationDAO
     */
    private $dao;
    /**
     * @var FilesystemInterface
     */
    private $filesystem;
    /**
     * @var LFSObjectPathAllocator
     */
    private $path_allocator;

    public function __construct(
        ActionAuthorizationDAO $dao,
        FilesystemInterface $filesystem,
        LFSObjectPathAllocator $path_allocator
    ) {
        $this->dao            = $dao;
        $this->filesystem     = $filesystem;
        $this->path_allocator = $path_allocator;
    }

    public function deleteExpired(\DateTimeImmutable $current_time)
    {
        $this->cleanDB($current_time);
        $this->cleanWorkFiles($current_time);
    }

    private function cleanDB(\DateTimeImmutable $current_time)
    {
        $this->dao->deleteByExpirationDate($current_time->getTimestamp());
    }

    private function cleanWorkFiles(\DateTimeImmutable $current_time)
    {
        $current_work_objects = [];
        foreach ($this->filesystem->listContents($this->path_allocator->getBasePathForSaveInProgressObject()) as $save_in_progress_object) {
            $path = $save_in_progress_object['path'];
            $current_work_objects[basename($path)] = [$path];
        }
        foreach ($this->filesystem->listContents($this->path_allocator->getBasePathForReadyToBeAvailableObject()) as $ready_object) {
            $path = $ready_object['path'];
            if (isset($current_work_objects[basename($path)])) {
                $current_work_objects[basename($path)][] = $path;
            } else {
                $current_work_objects[basename($path)] = [$path];
            }
        }

        $still_used_work_objects = $this->dao->searchExistingOIDsForAuthorizedActionByExpirationAndOIDs(
            $current_time->getTimestamp(),
            array_keys($current_work_objects)
        );
        $still_used_work_objects_by_oid = array_flip($still_used_work_objects);

        foreach ($current_work_objects as $current_work_object_id => $current_work_object_paths) {
            if (isset($still_used_work_objects_by_oid[$current_work_object_id])) {
                continue;
            }
            foreach ($current_work_object_paths as $path_to_remove) {
                $this->filesystem->deleteDir($path_to_remove);
            }
        }
    }
}
