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

namespace Tuleap\Artidoc\Upload\Section\File;

use ForgeConfig;
use Tuleap\Tus\NextGen\TusFileInformation;
use Tuleap\Upload\NextGen\PathAllocator;
use Tuleap\Upload\NextGen\UploadPathAllocator;

final readonly class ArtidocUploadPathAllocator implements PathAllocator
{
    private UploadPathAllocator $core_upload_path_allocator;

    public function __construct()
    {
        $this->core_upload_path_allocator = new UploadPathAllocator(
            ForgeConfig::get('tmp_dir') . '/artidoc/ongoing-sections-file-upload',
        );
    }

    public function getPathForItemBeingUploaded(TusFileInformation $file_information): string
    {
        return $this->core_upload_path_allocator->getPathForItemBeingUploaded($file_information);
    }

    /**
     * @return array<string,string>
     */
    public function getCurrentlyUsedAllocatedPathsPerExpectedItemIDs(): array
    {
        return $this->core_upload_path_allocator->getCurrentlyUsedAllocatedPathsPerExpectedItemIDs();
    }
}
