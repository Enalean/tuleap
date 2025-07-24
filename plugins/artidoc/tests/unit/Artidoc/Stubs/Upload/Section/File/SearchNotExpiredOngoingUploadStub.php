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

use Tuleap\Artidoc\Upload\Section\File\SearchNotExpiredOngoingUpload;
use Tuleap\Artidoc\Upload\Section\File\UploadFileInformation;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tus\Identifier\FileIdentifier;

final class SearchNotExpiredOngoingUploadStub implements SearchNotExpiredOngoingUpload
{
    public function __construct(private ?UploadFileInformation $file, private bool $should_raise_exception)
    {
    }

    public static function withFile(UploadFileInformation $file): self
    {
        return new self($file, false);
    }

    public static function withoutFile(): self
    {
        return new self(null, false);
    }

    public static function shouldNotBeCalled(): self
    {
        return new self(null, true);
    }

    #[\Override]
    public function searchNotExpiredOngoingUpload(FileIdentifier $id, int $user_id, \DateTimeImmutable $current_time): Ok|Err
    {
        if ($this->should_raise_exception) {
            throw new \Exception('Unexpected call to ' . __METHOD__);
        }

        return $this->file
            ? Result::ok($this->file)
            : Result::err(Fault::fromMessage('Not found'));
    }
}
