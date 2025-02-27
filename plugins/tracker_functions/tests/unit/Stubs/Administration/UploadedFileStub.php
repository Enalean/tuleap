<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\TrackerFunctions\Stubs\Administration;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

final class UploadedFileStub implements UploadedFileInterface
{
    private ?string $captured_moved_to_path = null;

    private function __construct(private readonly int $error, private readonly bool $should_raise_exception_on_move, private readonly int $size)
    {
    }

    public static function buildWithError(int $error): self
    {
        return new self($error, true, 0);
    }

    public static function buildWithExceptionOnMove(): self
    {
        return new self(UPLOAD_ERR_OK, true, 0);
    }

    public static function buildGreatSuccess(): self
    {
        return new self(UPLOAD_ERR_OK, false, 123);
    }

    public function getStream(): StreamInterface
    {
        // Not needed yet
        throw new \Exception('Not used yet');
    }

    public function moveTo($targetPath): void
    {
        if ($this->should_raise_exception_on_move) {
            throw new \Exception();
        }
        $this->captured_moved_to_path = $targetPath;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): null
    {
        return null;
    }

    public function getClientMediaType(): null
    {
        return null;
    }

    public function getCapturedMovedToPath(): ?string
    {
        return $this->captured_moved_to_path;
    }
}
