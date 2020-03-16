<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Upload;

use Tuleap\Tus\TusFileInformation;

final class UploadPathAllocator implements PathAllocator
{
    /**
     * @var string
     */
    private $base_path;

    public function __construct(string $base_path)
    {
        $this->base_path = $base_path;
    }

    public function getPathForItemBeingUploaded(TusFileInformation $file_information): string
    {
        return $this->base_path . '/' . $file_information->getID();
    }

    /**
     * @return array<string,string>
     */
    public function getCurrentlyUsedAllocatedPathsPerExpectedItemIDs(): array
    {
        if (! is_dir($this->base_path)) {
            return [];
        }

        $paths = [];

        $directory_iterator = new \DirectoryIterator($this->base_path);
        foreach ($directory_iterator as $file_info) {
            if ($file_info->isFile()) {
                $paths[$file_info->getFilename()] = $file_info->getPathname();
            }
        }

        return $paths;
    }
}
