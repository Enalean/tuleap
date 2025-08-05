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

use Tuleap\Artidoc\Upload\Section\File\SearchUpload;
use Tuleap\Artidoc\Upload\Section\File\UploadFileInformation;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tus\Identifier\FileIdentifier;

final readonly class SearchUploadStub implements SearchUpload
{
    public function __construct(private ?UploadFileInformation $file)
    {
    }

    public static function withFile(UploadFileInformation $file): self
    {
        return new self($file);
    }

    public static function withoutFile(): self
    {
        return new self(null);
    }

    #[\Override]
    public function searchUpload(FileIdentifier $id): Ok|Err
    {
        return $this->file
            ? Result::ok($this->file)
            : Result::err(Fault::fromMessage('Not found'));
    }
}
