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

use DateTimeImmutable;
use PFUser;
use Tuleap\Artidoc\Domain\Document\Artidoc;
use Tuleap\Artidoc\Upload\Section\File\CreateFileToUpload;
use Tuleap\Artidoc\Upload\Section\File\FileToUpload;
use Tuleap\Artidoc\Upload\Section\File\UploadCreationConflictFault;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tus\Identifier\FileIdentifier;

final readonly class CreateFileToUploadStub implements CreateFileToUpload
{
    private function __construct(private ?FileIdentifier $identifier)
    {
    }

    public static function withSuccessfulCreation(FileIdentifier $identifier): self
    {
        return new self($identifier);
    }

    public static function withCreationConflict(): self
    {
        return new self(null);
    }

    #[\Override]
    public function create(Artidoc $artidoc, PFUser $user, DateTimeImmutable $current_time, string $filename, int $filesize): Ok|Err
    {
        return $this->identifier
            ? Result::ok(new FileToUpload($this->identifier, $filename))
            : Result::err(UploadCreationConflictFault::build());
    }
}
