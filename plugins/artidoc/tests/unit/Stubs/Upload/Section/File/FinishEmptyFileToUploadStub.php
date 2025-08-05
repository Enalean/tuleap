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

namespace Tuleap\Artidoc\Stubs\Upload\Section\File;

use Tuleap\Artidoc\Upload\Section\File\CannotWriteFileFault;
use Tuleap\Artidoc\Upload\Section\File\FileToUpload;
use Tuleap\Artidoc\Upload\Section\File\FinishEmptyFileToUpload;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Upload\NextGen\PathAllocator;

final readonly class FinishEmptyFileToUploadStub implements FinishEmptyFileToUpload
{
    private function __construct(private ?bool $success)
    {
    }

    public static function shouldNotBeCalled(): self
    {
        return new self(null);
    }

    public static function withSuccessfulCreation(): self
    {
        return new self(true);
    }

    public static function withFailedCreation(): self
    {
        return new self(false);
    }

    #[\Override]
    public function createEmptyFile(FileToUpload $file_to_upload, PathAllocator $upload_path_allocator): Ok|Err
    {
        if ($this->success === null) {
            throw new \Exception('Unexpected call to ' . __METHOD__);
        }

        return $this->success
            ? Result::ok($file_to_upload)
            : Result::err(CannotWriteFileFault::build());
    }
}
