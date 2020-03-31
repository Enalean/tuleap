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

namespace Tuleap\Tracker\FormElement\Field\File\Upload\Tus;

use PFUser;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\REST\RESTCurrentUserMiddleware;
use Tuleap\Tracker\FormElement\Field\File\Upload\FileOngoingUploadDao;
use Tuleap\Tus\TusFileInformation;
use Tuleap\Tus\TusFileInformationProvider;
use Tuleap\Upload\FileBeingUploadedInformation;
use Tuleap\Upload\PathAllocator;

class FileBeingUploadedInformationProvider implements TusFileInformationProvider
{
    /**
     * @var PathAllocator
     */
    private $path_allocator;
    /**
     * @var FileOngoingUploadDao
     */
    private $dao;

    public function __construct(
        PathAllocator $path_allocator,
        FileOngoingUploadDao $dao
    ) {
        $this->path_allocator = $path_allocator;
        $this->dao            = $dao;
    }

    public function getFileInformation(ServerRequestInterface $request): ?TusFileInformation
    {
        $id = $request->getAttribute('id');

        if ($id === null) {
            return null;
        }

        $id = (int) $id;
        $current_user = $request->getAttribute(RESTCurrentUserMiddleware::class);
        \assert($current_user instanceof PFUser);

        return $this->getFileInformationByIdForUser($id, $current_user);
    }

    public function getFileInformationByIdForUser(int $id, PFUser $current_user): ?TusFileInformation
    {
        $row = $this->dao->searchFileOngoingUploadByIDUserIDAndExpirationDate(
            $id,
            (int) $current_user->getId(),
            (new \DateTimeImmutable())->getTimestamp()
        );
        if (empty($row)) {
            return null;
        }

        $filesize          = (int) $row['filesize'];
        $filename          = $row['filename'];
        $current_file_size = $this->getCurrentFileSize($id, $filename, $filesize);

        return new FileBeingUploadedInformation($id, $filename, $filesize, $current_file_size);
    }

    private function getCurrentFileSize(int $id, string $filename, int $length): int
    {
        $temporary_file_information = new FileBeingUploadedInformation($id, $filename, $length, 0);
        $allocated_path             = $this->path_allocator->getPathForItemBeingUploaded($temporary_file_information);

        $current_file_size = file_exists($allocated_path) ? filesize($allocated_path) : 0;

        return $current_file_size;
    }
}
