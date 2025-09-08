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

namespace Tuleap\PdfTemplate\Image;

use ForgeConfig;
use Tuleap\PdfTemplate\Admin\Image\StorePdfTemplateImage;
use Tuleap\PdfTemplate\Image\Identifier\PdfTemplateImageIdentifier;

final class PdfTemplateImageStorage implements StorePdfTemplateImage, DeleteImageFromStorage
{
    private const STORAGE_PATH = '/pdftemplate/images/';

    #[\Override]
    public function storeUploadedImage(string $uploaded_path, PdfTemplateImageIdentifier $identifier): bool
    {
        $destination = $this->getPath($identifier);
        $folder      = dirname($destination);
        if (! is_dir($folder) && ! mkdir($folder, 0755, true) && ! is_dir($folder)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $folder));
        }
        return move_uploaded_file($uploaded_path, $destination);
    }

    public function getPath(PdfTemplateImageIdentifier $identifier): string
    {
        return ForgeConfig::get('sys_data_dir') . self::STORAGE_PATH . $identifier->toString();
    }

    #[\Override]
    public function delete(PdfTemplateImage $image): void
    {
        $path = $this->getPath($image->identifier);
        if (is_file($path)) {
            unlink($path);
        }
    }
}
