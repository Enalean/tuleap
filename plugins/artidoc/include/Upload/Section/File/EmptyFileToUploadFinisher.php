<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Upload\Section\File;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Upload\NextGen\FileAlreadyUploadedInformation;
use Tuleap\Upload\NextGen\PathAllocator;

final readonly class EmptyFileToUploadFinisher implements FinishEmptyFileToUpload
{
    public function createEmptyFile(FileToUpload $file_to_upload, PathAllocator $upload_path_allocator): Ok|Err
    {
        $id               = $file_to_upload->id;
        $file_information = new FileAlreadyUploadedInformation($id, $file_to_upload->filename, 0);

        $path = $upload_path_allocator->getPathForItemBeingUploaded($file_information);

        $allocated_path_directory = dirname($path);
        if (
            ! \is_dir($allocated_path_directory) &&
            ! \mkdir($allocated_path_directory, 0777, true) &&
            ! \is_dir($allocated_path_directory)
        ) {
            return Result::err(CannotWriteFileFault::build());
        }
        touch($path);

        return Result::ok($file_to_upload);
    }
}
