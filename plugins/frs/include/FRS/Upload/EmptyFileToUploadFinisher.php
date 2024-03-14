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

namespace Tuleap\FRS\Upload;

use Tuleap\FRS\Upload\Tus\FileUploadFinisher;
use Tuleap\Tus\CannotWriteFileException;
use Tuleap\Upload\FileAlreadyUploadedInformation;

class EmptyFileToUploadFinisher
{
    /**
     * @var FileUploadFinisher
     */
    private $finisher;
    /**
     * @var UploadPathAllocator
     */
    private $upload_path_allocator;

    public function __construct(
        FileUploadFinisher $finisher,
        UploadPathAllocator $upload_path_allocator,
    ) {
        $this->finisher              = $finisher;
        $this->upload_path_allocator = $upload_path_allocator;
    }

    public function createEmptyFile(FileToUpload $file_to_upload, string $filename): void
    {
        $id               = $file_to_upload->getId();
        $file_information = new FileAlreadyUploadedInformation($id, $filename, 0);

        $path = $this->upload_path_allocator->getPathForItemBeingUploaded($file_information);

        $allocated_path_directory = dirname($path);
        if (
            ! \is_dir($allocated_path_directory) &&
            ! \mkdir($allocated_path_directory, 0777, true) &&
            ! \is_dir($allocated_path_directory)
        ) {
            throw new CannotWriteFileException();
        }
        if (! touch($path)) {
            throw new CannotWriteFileException();
        }

        $this->finisher->finishUploadFile($file_information);
    }
}
