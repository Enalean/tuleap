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

use Tuleap\Artidoc\Upload\Section\File\InsertFileToUpload;
use Tuleap\Artidoc\Upload\Section\File\SaveFileUpload;
use Tuleap\Tus\Identifier\FileIdentifier;

final class SaveFileUploadStub implements SaveFileUpload
{
    private ?InsertFileToUpload $saved = null;
    private function __construct(private readonly ?FileIdentifier $identifier)
    {
    }

    public static function withCreatedIdentifier(FileIdentifier $identifier): self
    {
        return new self($identifier);
    }

    public static function shouldNotBeCalled(): self
    {
        return new self(null);
    }

    #[\Override]
    public function saveFileOnGoingUpload(InsertFileToUpload $file_to_upload): FileIdentifier
    {
        if ($this->identifier === null) {
            throw new \Exception('Unexpected call to ' . __METHOD__);
        }

        $this->saved = $file_to_upload;

        return $this->identifier;
    }

    public function isCalled(): bool
    {
        return $this->saved !== null;
    }

    public function getSaved(): InsertFileToUpload
    {
        if ($this->saved === null) {
            throw new \Exception('Nothing has been saved');
        }

        return $this->saved;
    }
}
