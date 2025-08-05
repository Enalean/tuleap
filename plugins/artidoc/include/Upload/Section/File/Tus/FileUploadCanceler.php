<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Artidoc\Upload\Section\File\Tus;

use Tuleap\Artidoc\Upload\Section\File\ArtidocUploadPathAllocator;
use Tuleap\Artidoc\Upload\Section\File\DeleteFileUpload;
use Tuleap\Artidoc\Upload\Section\File\SearchUpload;
use Tuleap\Artidoc\Upload\Section\File\UploadFileInformation;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tus\NextGen\TusFileInformation;
use Tuleap\Tus\NextGen\TusTerminaterDataStore;

final readonly class FileUploadCanceler implements TusTerminaterDataStore
{
    public function __construct(private SearchUpload $search, private DeleteFileUpload $deletor)
    {
    }

    public function terminateUpload(TusFileInformation $file_information): void
    {
        $this->search
            ->searchUpload($file_information->getID())
            ->andThen($this->deleteFile(...));
    }

    /**
     * @return Ok<null>
     */
    private function deleteFile(UploadFileInformation $file_information): Ok
    {
        $this->deletor->deleteByID($file_information->getID());

        $path_allocator = ArtidocUploadPathAllocator::fromFileInformation($file_information);
        $file_path      = $path_allocator->getPathForItemBeingUploaded($file_information);
        if (\is_file($file_path)) {
            \unlink($file_path);
        }

        return Result::ok(null);
    }
}
