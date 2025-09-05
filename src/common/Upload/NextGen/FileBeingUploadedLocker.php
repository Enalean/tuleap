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

namespace Tuleap\Upload\NextGen;

use Tuleap\Tus\NextGen\TusFileInformation;
use Tuleap\Tus\NextGen\TusLocker;
use Tuleap\Upload\UploadLockVerificationException;

final class FileBeingUploadedLocker implements TusLocker
{
    /**
     * @var array<int, \SysvSemaphore>
     */
    private static $holded_semaphores = [];

    public function __construct(private readonly PathAllocator $path_allocator)
    {
    }

    #[\Override]
    public function lock(TusFileInformation $file_information): bool
    {
        return sem_acquire($this->createSemaphore($file_information), true);
    }

    #[\Override]
    public function unlock(TusFileInformation $file_information): void
    {
        $key = $this->getSemaphoreKey($file_information);
        if (isset(self::$holded_semaphores[$key])) {
            $semaphore = self::$holded_semaphores[$key];
            @sem_release(self::$holded_semaphores[$key]);
            unset(self::$holded_semaphores[$key]);
            @sem_remove($semaphore);
        }
    }

    private function createSemaphore(TusFileInformation $file_information): \SysvSemaphore
    {
        $key = $this->getSemaphoreKey($file_information);

        if (isset(self::$holded_semaphores[$key])) {
            return self::$holded_semaphores[$key];
        }

        $semaphore = sem_get($key);
        if ($semaphore === false) {
            throw new UploadLockVerificationException($this->getPathForFile($file_information));
        }
        self::$holded_semaphores[$key] = $semaphore;
        return $semaphore;
    }

    private function getSemaphoreKey(TusFileInformation $file_information): int
    {
        $file_path = $this->getPathForFile($file_information);

        /*
         * Get a quite unique 32 bits key for the file
         * ftok() is not used because it relies on the inode
         * that might be re-attributed immediately when the file
         * is deleted. This is particularly inconvenient when running tests.
         */
        return hexdec(substr(hash('sha256', $file_path), 0, 8));
    }

    private function getPathForFile(TusFileInformation $file_information): string
    {
        return $this->path_allocator->getPathForItemBeingUploaded($file_information);
    }
}
