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

namespace Tuleap\PdfTemplate\Stubs;

use Tuleap\PdfTemplate\Image\CreateImage;
use Tuleap\PdfTemplate\Image\Identifier\PdfTemplateImageIdentifier;
use Tuleap\PdfTemplate\Image\PdfTemplateImage;

final class CreateImageStub implements CreateImage
{
    private function __construct(private bool $created)
    {
    }

    public static function build(): self
    {
        return new self(false);
    }

    #[\Override]
    public function create(
        PdfTemplateImageIdentifier $identifier,
        string $filename,
        int $filesize,
        \PFUser $created_by,
        \DateTimeImmutable $created_date,
    ): PdfTemplateImage {
        $this->created = true;

        return new PdfTemplateImage($identifier, $filename, $filesize, $created_by, $created_date);
    }

    public function isCreated(): bool
    {
        return $this->created;
    }
}
