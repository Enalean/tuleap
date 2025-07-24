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

use Tuleap\PdfTemplate\Image\Identifier\PdfTemplateImageIdentifier;
use Tuleap\PdfTemplate\Admin\Image\StorePdfTemplateImage;

final readonly class StorePdfTemplateImageStub implements StorePdfTemplateImage
{
    private function __construct(public ?bool $result)
    {
    }

    public static function withSuccessfulUpload(): self
    {
        return new self(true);
    }

    public static function withFailingUpload(): self
    {
        return new self(false);
    }

    public static function shouldNotBeCalled(): self
    {
        return new self(null);
    }

    #[\Override]
    public function storeUploadedImage(string $uploaded_path, PdfTemplateImageIdentifier $identifier): bool
    {
        if ($this->result === null) {
            throw new \Exception('Unexpected call to ' . __METHOD__);
        }

        return $this->result;
    }
}
