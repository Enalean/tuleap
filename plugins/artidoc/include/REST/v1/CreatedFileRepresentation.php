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

namespace Tuleap\Artidoc\REST\v1;

use Tuleap\Artidoc\Upload\Section\File\FileToUpload;

/**
 * @psalm-immutable
 */
final readonly class CreatedFileRepresentation
{
    public string $id;
    public string $download_href;
    /**
     * @var ?string URL to upload the file using the <a href="https://tus.io/protocols/resumable-upload.html">tus resumable upload protocol</a>
     */
    public ?string $upload_href;

    public function __construct(FileToUpload $file_to_upload, int $file_size)
    {
        $this->id            = $file_to_upload->id->toString();
        $this->download_href = $file_to_upload->getDownloadHref();

        if ($file_size !== 0) {
            $this->upload_href = $file_to_upload->getUploadHref();
        }
    }
}
